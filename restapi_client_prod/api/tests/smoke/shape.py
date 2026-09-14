"""Contract-shape helpers for restapi_client_prod smoke (no payloads)."""

from __future__ import annotations

from typing import Any, List, Optional


def _sorted_keys(obj: Any) -> Optional[List[str]]:
    if isinstance(obj, dict):
        return sorted(obj.keys())
    return None


def _first_row(payload: Any) -> Any:
    if isinstance(payload, dict):
        data = payload.get("data")
        if isinstance(data, list) and data:
            return data[0]
        if isinstance(data, dict) and data:
            first = next(iter(data.values()))
            if isinstance(first, dict):
                return first
            if isinstance(first, list) and first and isinstance(first[0], dict):
                return first[0]
        if "data" not in payload:
            if "error" in payload:
                return None
            return payload
        return None
    if isinstance(payload, list) and payload:
        return payload[0]
    return None


def _count(payload: Any) -> Optional[int]:
    if isinstance(payload, list):
        return len(payload)
    if isinstance(payload, dict):
        data = payload.get("data")
        if isinstance(data, list):
            return len(data)
        if isinstance(data, dict):
            return len(data)
    return None


def summarize_response(status, payload, elapsed_s=0.0, raw_prefix=""):
    """Return comparable contract fields without business values."""
    is_json = payload is not None
    kind = None
    keys = None
    error = None
    if isinstance(payload, dict):
        kind = "object"
        keys = _sorted_keys(payload)
        if "error" in payload:
            error = bool(payload.get("error"))
    elif isinstance(payload, list):
        kind = "list"
        keys = None
    row = _first_row(payload) if is_json else None
    row_keys = _sorted_keys(row) if isinstance(row, dict) else None
    shape = {
        "status": int(status),
        "json": is_json,
        "kind": kind,
        "keys": keys,
        "error": error,
        "count": _count(payload) if is_json else None,
        "row_keys": row_keys,
        "elapsed_s": round(float(elapsed_s), 3),
    }
    if not is_json and raw_prefix:
        shape["raw_prefix"] = raw_prefix[:200]
    return shape


def compare_shapes(baseline, current):
    """Diff contract fields. count and elapsed_s are informational only."""
    diffs = []
    for field in ("status", "json", "kind", "keys", "error", "row_keys"):
        if baseline.get(field) != current.get(field):
            diffs.append(
                "%s: %r -> %r" % (field, baseline.get(field), current.get(field))
            )
    return diffs


def compare_env_shapes(left, right):
    """Compare local vs public. Ignore counts and empty-vs-filled first rows."""
    diffs = []
    for field in ("status", "json"):
        if left.get(field) != right.get(field):
            diffs.append(
                "%s: %r -> %r" % (field, left.get(field), right.get(field))
            )
    if not left.get("json") or not right.get("json"):
        return diffs
    if left.get("kind") != right.get("kind"):
        diffs.append("kind: %r -> %r" % (left.get("kind"), right.get("kind")))
    if left.get("error") != right.get("error"):
        diffs.append("error: %r -> %r" % (left.get("error"), right.get("error")))
    left_rows = left.get("row_keys")
    right_rows = right.get("row_keys")
    if left_rows and right_rows and left_rows != right_rows:
        diffs.append("row_keys: %r -> %r" % (left_rows, right_rows))
    return diffs
