#!/usr/bin/env python3
"""Smoke HTTP: recetas Odoo contra API local y/o api-traldisporta.com.

Compara forma (status, JSON, error, row_keys), no payloads ni recuentos.
No llama send_report_morosos / newUser / newPassword / refreshShowvehiclesCache.

Env:
  API_KEY     apikey (Odoo: traldisporta.url.webservice.apikey)
  DATE_INIT / DATE_END   default 2024-06-01 / 2024-06-02 (seed local)
  KPI_YEAR / KPI_MONTH   default 2024 / 6
  BASE_URL    si no se usa --local/--public/--compare-envs

Usage:
  python3 prod_contract.py --auth-only
  API_KEY=... python3 prod_contract.py --local
  API_KEY=... python3 prod_contract.py --public
  API_KEY=... python3 prod_contract.py --compare-envs
"""

from __future__ import annotations

import argparse
import json
import os
import sys
from datetime import date
from pathlib import Path

import requests

from shape import compare_env_shapes, compare_shapes, summarize_response

HERE = Path(__file__).resolve().parent
FIXTURES = HERE.parent / "fixtures" / "php74"
OUT_DIR = HERE / "out"
ENVS = {
    "local": "http://127.0.0.1:8080/restapi/v1",
    "public": "https://api-traldisporta.com/api/v1",
}
DEFAULT_BASE = ENVS["public"]
SKIP_PATHS = frozenset(
    {
        "/morosos/send_report_morosos",
        "/newUser",
        "/newPassword",
        "/refreshShowvehiclesCache",
    }
)


def _session():
    s = requests.Session()
    s.headers.update({"Content-Type": "application/json", "Accept": "application/json"})
    return s


def post_raw(session, base_url, path, body, api_key=None, timeout=30):
    if path in SKIP_PATHS:
        raise RuntimeError("refusing to call %s" % path)
    headers = {"Checksum": ""}
    if api_key:
        token = str(api_key).strip()
        headers["Authorization"] = token
        headers["Apikey"] = token
        headers["apikey"] = token
    url = base_url.rstrip("/") + path
    resp = session.post(url, json=body, headers=headers, timeout=timeout)
    prefix = (resp.text or "")[:200]
    try:
        payload = resp.json()
    except ValueError:
        payload = None
    shape = summarize_response(
        resp.status_code, payload, resp.elapsed.total_seconds(), prefix
    )
    return shape, payload


def post(session, base_url, path, body, api_key=None, timeout=30):
    shape, _payload = post_raw(session, base_url, path, body, api_key, timeout)
    return shape


def default_window():
    init = os.environ.get("DATE_INIT")
    end = os.environ.get("DATE_END")
    if init and end:
        return init, end
    return "2024-06-01", "2024-06-02"


def default_kpi():
    year = os.environ.get("KPI_YEAR")
    month = os.environ.get("KPI_MONTH")
    if year and month:
        return int(year), int(month)
    return 2024, 6


def write_json(path, data):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(data, indent=2, sort_keys=True) + "\n")


def odoo_catalog(include_slow):
    date_init, date_end = default_window()
    kpi_year, kpi_month = default_kpi()
    token_body = {"token": ""}
    dates = {"dateInit": date_init, "dateEnd": date_end}
    cases = [
        {"name": "companies", "path": "/companies", "body": token_body},
        {"name": "centers", "path": "/centers", "body": token_body},
        {"name": "categories", "path": "/categories", "body": token_body},
        {"name": "sections", "path": "/sections", "body": token_body},
        {"name": "sectors", "path": "/sectors", "body": token_body},
        {
            "name": "agenda",
            "path": "/agenda",
            "body": {"token": "aaa", "type": "ALL"},
            "timeout": 120,
        },
        {"name": "getSaleInvoices", "path": "/getSaleInvoices", "body": dates, "timeout": 60},
        {"name": "getBuyInvoices", "path": "/getBuyInvoices", "body": dates, "timeout": 60},
        {"name": "getAllMovements", "path": "/getAllMovements", "body": dates, "timeout": 60},
        {
            "name": "getMonthlyMovements",
            "path": "/getMonthlyMovements",
            "body": {"year": kpi_year, "month": kpi_month},
            "timeout": 60,
        },
        {
            "name": "morosos_facturas_pendientes",
            "path": "/morosos/facturas_pendientes",
            "body": dict(dates, salesman=""),
            "timeout": 60,
        },
    ]
    if include_slow:
        cases.append(
            {
                "name": "getExpeditionsData",
                "path": "/getExpeditionsData",
                "body": {
                    "year": str(kpi_year),
                    "month": str(kpi_month),
                    "centerCode": "8",
                    "startDate": "2024-06-15",
                    "endDate": "2024-06-21",
                },
                "timeout": 240,
            }
        )
    return cases


def auth_cases(session, base_url):
    return {
        "auth_missing_key": post(session, base_url, "/companies", {"token": ""}),
        "auth_invalid_key": post(
            session, base_url, "/companies", {"token": ""}, api_key="invalid-key"
        ),
    }


def run_catalog(session, base_url, api_key, include_slow):
    results = auth_cases(session, base_url)
    if not api_key:
        return results
    for case in odoo_catalog(include_slow):
        timeout = case.get("timeout", 30)
        try:
            results[case["name"]] = post(
                session, base_url, case["path"], case["body"], api_key, timeout
            )
        except requests.RequestException as exc:
            results[case["name"]] = {
                "status": 0,
                "json": False,
                "kind": None,
                "keys": None,
                "error": None,
                "count": None,
                "row_keys": None,
                "elapsed_s": 0,
                "raw_prefix": str(exc)[:200],
            }
    return results


def expected_auth(results):
    problems = []
    missing = results.get("auth_missing_key") or {}
    if missing.get("status") != 400 or missing.get("error") is not True:
        problems.append("missing key: expected HTTP 400 error=true, got %s" % missing)
    invalid = results.get("auth_invalid_key") or {}
    if invalid.get("status") not in (201, 400) or invalid.get("error") is not True:
        problems.append(
            "invalid key: expected HTTP 400 or 201 error=true, got %s" % invalid
        )
    return problems


def http_problems(results):
    problems = expected_auth(results)
    for name, shape in sorted(results.items()):
        if name.startswith("auth_"):
            continue
        if not shape.get("json"):
            problems.append("%s: not JSON (status=%s)" % (name, shape.get("status")))
        elif shape.get("status") >= 400:
            problems.append(
                "%s: HTTP %s error=%s" % (name, shape.get("status"), shape.get("error"))
            )
    return problems


def print_shape_table(label, results):
    print("--- %s ---" % label)
    for name in sorted(results):
        s = results[name]
        print(
            "  %-28s status=%s json=%s error=%s kind=%s count=%s"
            % (
                name,
                s.get("status"),
                s.get("json"),
                s.get("error"),
                s.get("kind"),
                s.get("count"),
            )
        )


def main(argv=None):
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--auth-only", action="store_true")
    parser.add_argument("--save", action="store_true")
    parser.add_argument("--compare", action="store_true")
    parser.add_argument("--local", action="store_true", help=ENVS["local"])
    parser.add_argument("--public", action="store_true", help=ENVS["public"])
    parser.add_argument(
        "--compare-envs",
        action="store_true",
        help="run local and public with the same Odoo recipes, diff shapes",
    )
    parser.add_argument("--include-slow", action="store_true")
    args = parser.parse_args(argv)

    session = _session()
    api_key = (os.environ.get("API_KEY") or "").strip() or None
    if args.auth_only:
        api_key = None

    if args.compare_envs:
        args.local = True
        args.public = True

    targets = []
    if args.local:
        targets.append(("local", ENVS["local"]))
    if args.public:
        targets.append(("public", ENVS["public"]))
    if not targets:
        targets.append(("default", os.environ.get("BASE_URL", DEFAULT_BASE).rstrip("/")))

    if not args.auth_only and not api_key:
        print("API_KEY unset: only auth cases will run.", file=sys.stderr)

    stamp = date.today().isoformat()
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    by_env = {}
    problems = []

    for name, base in targets:
        results = run_catalog(session, base, api_key, args.include_slow)
        by_env[name] = results
        out_file = OUT_DIR / ("shape-%s-%s.json" % (name, stamp))
        write_json(out_file, results)
        print("wrote %s" % out_file)
        print_shape_table("%s %s" % (name, base), results)
        for item in http_problems(results):
            problems.append("[%s] %s" % (name, item))

    if args.save and targets:
        name, results = next(iter(by_env.items()))
        FIXTURES.mkdir(parents=True, exist_ok=True)
        saved = 0
        for case, shape in results.items():
            if not case.startswith("auth_") and shape.get("status", 0) >= 400:
                continue
            stored = {k: v for k, v in shape.items() if k != "elapsed_s"}
            write_json(FIXTURES / ("%s.json" % case), stored)
            saved += 1
        print("saved %d fixtures from %s under %s" % (saved, name, FIXTURES))

    if args.compare:
        results = next(iter(by_env.values()))
        for case, shape in results.items():
            path = FIXTURES / ("%s.json" % case)
            if not path.exists():
                problems.append("missing fixture %s" % path.name)
                continue
            diffs = compare_shapes(json.loads(path.read_text()), shape)
            if diffs:
                problems.append("%s: %s" % (case, "; ".join(diffs)))

    if args.compare_envs and "local" in by_env and "public" in by_env:
        print("--- local vs public ---")
        names = sorted(set(by_env["local"]) | set(by_env["public"]))
        for case in names:
            left = by_env["local"].get(case)
            right = by_env["public"].get(case)
            if left is None or right is None:
                problems.append("[compare] %s missing in one env" % case)
                print("  %-28s MISSING" % case)
                continue
            diffs = compare_env_shapes(left, right)
            if diffs:
                problems.append("[compare] %s: %s" % (case, "; ".join(diffs)))
                print("  %-28s DIFF %s" % (case, "; ".join(diffs)))
            else:
                print("  %-28s OK" % case)

    for line in problems:
        print("FAIL %s" % line, file=sys.stderr)
    print("problems=%d" % len(problems))
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
