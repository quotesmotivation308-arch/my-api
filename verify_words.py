import os
import json
import hashlib
import pymysql
from dotenv import load_dotenv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_FILE = os.path.join(BASE_DIR, "custom_evp_uk1.json")

load_dotenv(os.path.join(BASE_DIR, ".env"))

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_DATABASE = os.getenv("DB_DATABASE")
DB_USERNAME = os.getenv("DB_USERNAME")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")


def canonical_json(value):
    return json.dumps(
        value,
        ensure_ascii=False,
        sort_keys=True,
        separators=(",", ":")
    )


def sha256(value):
    return hashlib.sha256(
        canonical_json(value).encode("utf-8")
    ).hexdigest()


print("Loading JSON...")
with open(JSON_FILE, "r", encoding="utf-8") as f:
    records = json.load(f)

if not isinstance(records, list):
    raise RuntimeError("JSON root is not an array.")

print(f"JSON records: {len(records):,}")

print("Connecting to MySQL...")

connection = pymysql.connect(
    host=DB_HOST,
    port=DB_PORT,
    user=DB_USERNAME,
    password=DB_PASSWORD,
    database=DB_DATABASE,
    charset="utf8mb4",
    cursorclass=pymysql.cursors.DictCursor,
)

try:
    with connection.cursor() as cursor:

        # ---------------------------------------------------------
        # 1. Count
        # ---------------------------------------------------------

        cursor.execute("SELECT COUNT(*) AS total FROM words")
        db_count = cursor.fetchone()["total"]

        print(f"Database records: {db_count:,}")

        if db_count != len(records):
            raise RuntimeError(
                f"COUNT MISMATCH: JSON={len(records):,}, DB={db_count:,}"
            )

        print("✓ Record count matches.")

        # ---------------------------------------------------------
        # 2. Load database records by _id
        # ---------------------------------------------------------

        cursor.execute("""
            SELECT
                _id,
                _version,
                _type,
                id_text,
                refid_text,
                source_json
            FROM words
        """)

        db_rows = cursor.fetchall()

        db_by_id = {
            row["_id"]: row
            for row in db_rows
        }

        if len(db_by_id) != db_count:
            raise RuntimeError(
                "Duplicate _id values detected in database."
            )

        print("✓ Database _id values are unique.")

        # ---------------------------------------------------------
        # 3. Verify every original JSON record
        # ---------------------------------------------------------

        checked = 0

        for record in records:

            outer_id = record.get("_id")
            outer_version = record.get("_version")
            outer_type = record.get("_type")

            source = record.get("_source", {})

            source_id = source.get("_id")
            source_version = source.get("_version")
            source_type = source.get("_type")

            id_text = source.get("id_text")
            refid_text = source.get("refid_text")

            # Make sure source itself is internally consistent
            if outer_id != source_id:
                raise RuntimeError(
                    f"Original JSON _id mismatch: {outer_id}"
                )

            if outer_version != source_version:
                raise RuntimeError(
                    f"Original JSON _version mismatch: {outer_id}"
                )

            if outer_type != source_type:
                raise RuntimeError(
                    f"Original JSON _type mismatch: {outer_id}"
                )

            if outer_id not in db_by_id:
                raise RuntimeError(
                    f"Missing database record: _id={outer_id}"
                )

            row = db_by_id[outer_id]

            # -----------------------------------------------------
            # Identity fields
            # -----------------------------------------------------

            if row["_version"] != outer_version:
                raise RuntimeError(
                    f"_version mismatch: _id={outer_id}"
                )

            if row["_type"] != outer_type:
                raise RuntimeError(
                    f"_type mismatch: _id={outer_id}"
                )

            if row["id_text"] != id_text:
                raise RuntimeError(
                    f"id_text mismatch: _id={outer_id}"
                )

            if row["refid_text"] != refid_text:
                raise RuntimeError(
                    f"refid_text mismatch: _id={outer_id}"
                )

            # -----------------------------------------------------
            # Full original JSON comparison
            # -----------------------------------------------------

            db_source_json = row["source_json"]

            if isinstance(db_source_json, str):
                db_source_json = json.loads(db_source_json)

            original_hash = sha256(record)
            database_hash = sha256(db_source_json)

            if original_hash != database_hash:
                raise RuntimeError(
                    f"source_json mismatch: _id={outer_id}"
                )

            checked += 1

            if checked % 1000 == 0:
                print(f"Verified: {checked:,} / {len(records):,}")

        # ---------------------------------------------------------
        # 4. Check for unexpected database _ids
        # ---------------------------------------------------------

        json_ids = {
            record["_id"]
            for record in records
        }

        db_ids = set(db_by_id.keys())

        extra_db_ids = db_ids - json_ids

        if extra_db_ids:
            raise RuntimeError(
                f"Database contains {len(extra_db_ids)} unexpected _id values."
            )

        # ---------------------------------------------------------
        # 5. Duplicate id_text check in original JSON
        # ---------------------------------------------------------

        json_id_texts = [
            record["_source"]["id_text"]
            for record in records
        ]

        if len(json_id_texts) != len(set(json_id_texts)):
            raise RuntimeError(
                "Duplicate id_text values detected in JSON."
            )

        print("✓ id_text values are unique.")

        # ---------------------------------------------------------
        # Final
        # ---------------------------------------------------------

        print()
        print("=" * 60)
        print("VERIFICATION SUCCESSFUL")
        print("=" * 60)
        print(f"JSON records       : {len(records):,}")
        print(f"Database records   : {db_count:,}")
        print(f"Records verified   : {checked:,}")
        print("Identity fields    : OK")
        print("source_json        : OK")
        print("Unexpected records : 0")
        print("=" * 60)

finally:
    connection.close()