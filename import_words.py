import json
import os
import sys
import pymysql


BASE_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_FILE = os.path.join(BASE_DIR, "custom_evp_uk1.json")


def load_env():
    env = {}

    env_file = os.path.join(BASE_DIR, ".env")

    if not os.path.exists(env_file):
        raise FileNotFoundError(f".env not found: {env_file}")

    with open(env_file, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()

            if not line or line.startswith("#") or "=" not in line:
                continue

            key, value = line.split("=", 1)

            key = key.strip()
            value = value.strip().strip('"').strip("'")

            env[key] = value

    return env


def json_value(value):
    if value is None:
        return None

    return json.dumps(
        value,
        ensure_ascii=False,
        separators=(",", ":")
    )


def main():

    print("Loading .env...")

    env = load_env()

    db_host = env.get("DB_HOST", "127.0.0.1")
    db_port = int(env.get("DB_PORT", "3306"))
    db_name = env.get("DB_DATABASE")
    db_user = env.get("DB_USERNAME")
    db_password = env.get("DB_PASSWORD", "")

    if not db_name:
        raise RuntimeError("DB_DATABASE is missing in .env")

    if not db_user:
        raise RuntimeError("DB_USERNAME is missing in .env")

    if not os.path.exists(JSON_FILE):
        raise FileNotFoundError(f"JSON file not found: {JSON_FILE}")

    print(f"JSON: {JSON_FILE}")
    print(f"Database: {db_name}")
    print()

    print("Reading JSON...")

    with open(JSON_FILE, "r", encoding="utf-8") as f:
        data = json.load(f)

    if not isinstance(data, list):
        raise ValueError("Root JSON value must be an array.")

    print(f"JSON records: {len(data):,}")

    connection = pymysql.connect(
        host=db_host,
        port=db_port,
        user=db_user,
        password=db_password,
        database=db_name,
        charset="utf8mb4",
        autocommit=False,
    )

    cursor = connection.cursor()

    columns = [
        "found",
        "_version",
        "_type",
        "_id",

        "id_text",
        "pos_text",
        "base_text",
        "Created By",
        "refid_text",
        "ukpron_text",
        "Created Date",
        "Modified Date",
        "cefr_text_text",
        "culture_number",
        "guideword_text",
        "definition_text",
        "searchterms_text",
        "audiofilename_text",
        "cefr_custom_evp_cefr",
        "learnerexamples_text",
        "pos_custom_evp_l_pos",
        "l_category_custom_evp_l_category",
        "l_grammars_list_custom_evp_l_grammar",
        "l_suffix_custom_evp_l_suffix",
        "l_topic_text_text",
        "l_topics_list_custom_evp_l_topic",
        "hw_text",

        "source_json",
    ]

    quoted_columns = ", ".join(
        f"`{column}`" for column in columns
    )

    placeholders = ", ".join(["%s"] * len(columns))

    sql = f"""
        INSERT INTO `words`
        ({quoted_columns})
        VALUES ({placeholders})
    """

    rows = []

    seen_ids = set()
    seen_id_texts = set()

    for index, record in enumerate(data, start=1):

        if not isinstance(record, dict):
            raise ValueError(
                f"Record #{index} is not an object."
            )

        source = record.get("_source")

        if not isinstance(source, dict):
            raise ValueError(
                f"Record #{index} has no valid _source."
            )

        outer_id = record.get("_id")
        source_id = source.get("_id")

        outer_version = record.get("_version")
        source_version = source.get("_version")

        outer_type = record.get("_type")
        source_type = source.get("_type")

        # Verify duplicated JSON values are actually identical.
        if outer_id != source_id:
            raise ValueError(
                f"_id mismatch at record #{index}: "
                f"outer={outer_id!r}, source={source_id!r}"
            )

        if outer_version != source_version:
            raise ValueError(
                f"_version mismatch at record #{index}: "
                f"outer={outer_version!r}, source={source_version!r}"
            )

        if outer_type != source_type:
            raise ValueError(
                f"_type mismatch at record #{index}: "
                f"outer={outer_type!r}, source={source_type!r}"
            )

        id_text = source.get("id_text")

        if not id_text:
            raise ValueError(
                f"Missing id_text at record #{index}"
            )

        if outer_id in seen_ids:
            raise ValueError(
                f"Duplicate _id inside JSON: {outer_id}"
            )

        if id_text in seen_id_texts:
            raise ValueError(
                f"Duplicate id_text inside JSON: {id_text}"
            )

        seen_ids.add(outer_id)
        seen_id_texts.add(id_text)

        rows.append((
            1 if record.get("found") is True else 0,

            outer_version,
            outer_type,
            outer_id,

            source.get("id_text"),
            source.get("pos_text"),
            source.get("base_text"),
            source.get("Created By"),
            source.get("refid_text"),
            source.get("ukpron_text"),
            source.get("Created Date"),
            source.get("Modified Date"),
            source.get("cefr_text_text"),
            source.get("culture_number"),
            source.get("guideword_text"),
            source.get("definition_text"),
            source.get("searchterms_text"),
            source.get("audiofilename_text"),
            source.get("cefr_custom_evp_cefr"),
            source.get("learnerexamples_text"),
            source.get("pos_custom_evp_l_pos"),
            source.get("l_category_custom_evp_l_category"),

            json_value(
                source.get("l_grammars_list_custom_evp_l_grammar")
            ),

            source.get("l_suffix_custom_evp_l_suffix"),
            source.get("l_topic_text_text"),

            json_value(
                source.get("l_topics_list_custom_evp_l_topic")
            ),

            source.get("hw_text"),

            # Preserve the complete original JSON object.
            json_value(record),
        ))

    print(f"Validated records: {len(rows):,}")
    print("Checking database...")

    cursor.execute("SELECT COUNT(*) FROM `words`")
    existing_count = cursor.fetchone()[0]

    print(f"Existing words rows: {existing_count:,}")

    if existing_count != 0:
        raise RuntimeError(
            "The words table is not empty. "
            "Import stopped to prevent duplicate data."
        )

    print()
    print("Starting transaction...")

    try:
        batch_size = 1000

        for start in range(0, len(rows), batch_size):

            batch = rows[start:start + batch_size]

            cursor.executemany(sql, batch)

            inserted = min(
                start + batch_size,
                len(rows)
            )

            print(
                f"Inserted: {inserted:,} / {len(rows):,}"
            )

        connection.commit()

    except Exception:
        connection.rollback()
        print()
        print("ERROR: Transaction rolled back.")
        raise

    finally:
        cursor.close()
        connection.close()

    print()
    print("Import completed successfully.")


if __name__ == "__main__":
    try:
        main()
    except Exception as e:
        print()
        print("IMPORT FAILED")
        print(str(e))
        sys.exit(1)