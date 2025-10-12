from pathlib import Path
from collections import OrderedDict

SOURCE_PATH = Path("_travian/schema/T4.4.sql")
OUTPUT_PATH = Path("docs/schema/T4.4_selected_tables.sql")

CATEGORIES = OrderedDict([
    (
        "Auth/User",
        [
            "users",
            "activation",
            "login_handshake",
            "activation_progress",
            "deleting",
            "newproc",
        ],
    ),
    (
        "Villages",
        [
            "vdata",
            "fdata",
            "wdata",
            "available_villages",
            "odata",
            "building_upgrade",
            "demolition",
            "smithy",
            "tdata",
            "research",
            "traderoutes",
        ],
    ),
    (
        "Alliances",
        [
            "alidata",
            "alistats",
            "allimedal",
            "ali_invite",
            "ali_log",
            "diplomacy",
            "alliance_notification",
            "alliance_bonus_upgrade_queue",
            "forum_forums",
            "forum_edit",
            "forum_options",
            "forum_post",
            "forum_vote",
            "forum_open_players",
            "forum_open_alliances",
            "forum_topic",
        ],
    ),
    (
        "Combat/Movement",
        [
            "movement",
            "a2b",
            "enforcement",
            "trapped",
            "units",
            "send",
        ],
    ),
    (
        "Hero",
        [
            "hero",
            "face",
            "items",
            "inventory",
            "adventure",
            "accounting",
        ],
    ),
    (
        "Artifacts",
        [
            "artefacts",
            "artlog",
        ],
    ),
    (
        "Communication",
        [
            "mdata",
            "ndata",
            "messages_report",
            "notes",
        ],
    ),
    (
        "Market",
        [
            "market",
            "auction",
            "bids",
            "traderoutes",
            "raidlist",
        ],
    ),
    (
        "Map",
        [
            "map_block",
            "map_mark",
            "mapflag",
            "marks",
            "blocks",
            "surrounding",
        ],
    ),
    (
        "Quests/Medals",
        [
            "daily_quest",
            "medal",
            "allimedal",
        ],
    ),
    (
        "Farmlist",
        [
            "farmlist",
            "raidlist",
            "farmlist_last_reports",
        ],
    ),
    (
        "Admin/Logs",
        [
            "general_log",
            "admin_log",
            "log_ip",
            "transfer_gold_log",
            "banHistory",
            "banQueue",
            "multiaccount_log",
            "multiaccount_users",
        ],
    ),
    (
        "Config",
        [
            "config",
            "summary",
            "casualties",
            "autoExtend",
        ],
    ),
    (
        "Misc",
        [
            "links",
            "infobox",
            "infobox_read",
            "infobox_delete",
            "ignoreList",
            "friendlist",
            "changeEmail",
            "notificationQueue",
            "voting_reward_queue",
            "buyGoldMessages",
            "player_references",
        ],
    ),
])


def extract_blocks(source: Path) -> dict[str, str]:
    text = source.read_text(encoding="utf-8")
    marker = "DROP TABLE IF EXISTS `"
    entries: list[tuple[str, int]] = []
    index = 0
    while True:
        next_index = text.find(marker, index)
        if next_index == -1:
            break
        start = next_index + len(marker)
        end = text.find("`", start)
        if end == -1:
            break
        table_name = text[start:end]
        entries.append((table_name, next_index))
        index = end

    blocks: dict[str, str] = {}
    for position, (table_name, start) in enumerate(entries):
        next_start = entries[position + 1][1] if position + 1 < len(entries) else len(text)
        snippet = text[start:next_start].strip()
        blocks.setdefault(table_name, snippet)
    return blocks


def main() -> None:
    if not SOURCE_PATH.exists():
        raise FileNotFoundError(f"Cannot find source schema at {SOURCE_PATH}")

    blocks = extract_blocks(SOURCE_PATH)

    lines: list[str] = []
    lines.append("-- Extracted Travian T4.4 schema for selected categories")
    lines.append(f"-- Source: {SOURCE_PATH}")
    lines.append("")

    cache: dict[str, str] = {}

    for category, tables in CATEGORIES.items():
        lines.append(f"-- =============================================")
        lines.append(f"-- Category: {category}")
        lines.append(f"-- =============================================")
        lines.append("")
        for table in tables:
            if table not in blocks:
                raise KeyError(f"Table '{table}' not found in source schema")
            if table not in cache:
                cache[table] = blocks[table]
            lines.append(f"-- Table: {table}")
            lines.append(cache[table])
            lines.append("")

    OUTPUT_PATH.write_text("\n".join(lines).rstrip() + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
