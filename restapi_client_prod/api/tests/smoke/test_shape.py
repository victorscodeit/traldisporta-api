#!/usr/bin/env python3
"""Unit tests for contract-shape helpers (no network)."""
import unittest

from shape import compare_env_shapes, compare_shapes, summarize_response


class SummarizeResponseTest(unittest.TestCase):
    def test_wrapped_list_keeps_envelope_and_row_keys(self):
        shape = summarize_response(
            200,
            {"error": False, "message": "OK", "data": [{"id": 1, "name": "A"}]},
            elapsed_s=0.12,
        )
        self.assertEqual(shape["status"], 200)
        self.assertTrue(shape["json"])
        self.assertEqual(shape["keys"], ["data", "error", "message"])
        self.assertFalse(shape["error"])
        self.assertEqual(shape["count"], 1)
        self.assertEqual(shape["row_keys"], ["id", "name"])
        self.assertNotIn("name", shape)

    def test_raw_array_uses_first_row_keys(self):
        shape = summarize_response(
            200,
            [{"RegNum": 100, "RegTip": "V"}, {"RegNum": 101, "RegTip": "V"}],
            elapsed_s=1.0,
        )
        self.assertEqual(shape["kind"], "list")
        self.assertEqual(shape["count"], 2)
        self.assertEqual(shape["row_keys"], ["RegNum", "RegTip"])
        self.assertIsNone(shape["error"])

    def test_error_envelope_has_no_row_keys(self):
        shape = summarize_response(
            400,
            {"error": True, "message": "missing"},
            elapsed_s=0.01,
        )
        self.assertTrue(shape["error"])
        self.assertIsNone(shape["row_keys"])

    def test_non_json_is_flagged(self):
        shape = summarize_response(500, None, elapsed_s=0.01, raw_prefix="<html>")
        self.assertFalse(shape["json"])
        self.assertEqual(shape["raw_prefix"], "<html>")


class CompareShapesTest(unittest.TestCase):
    def test_count_drift_is_not_a_break(self):
        baseline = {
            "status": 200,
            "json": True,
            "kind": "object",
            "keys": ["data", "error", "message"],
            "error": False,
            "count": 10,
            "row_keys": ["id", "name"],
        }
        current = dict(baseline)
        current["count"] = 12
        diffs = compare_shapes(baseline, current)
        self.assertEqual(diffs, [])

    def test_status_or_keys_change_is_a_break(self):
        baseline = {
            "status": 200,
            "json": True,
            "kind": "object",
            "keys": ["data", "error"],
            "error": False,
            "count": 1,
            "row_keys": ["id"],
        }
        current = dict(baseline)
        current["status"] = 500
        current["keys"] = ["error"]
        diffs = compare_shapes(baseline, current)
        self.assertTrue(any("status" in d for d in diffs))
        self.assertTrue(any("keys" in d for d in diffs))


class CompareEnvShapesTest(unittest.TestCase):
    def test_empty_vs_filled_list_is_not_a_break(self):
        local = {
            "status": 200,
            "json": True,
            "kind": "list",
            "error": None,
            "count": 0,
            "row_keys": None,
        }
        public = {
            "status": 200,
            "json": True,
            "kind": "list",
            "error": None,
            "count": 32,
            "row_keys": ["RegNum", "RegTip"],
        }
        self.assertEqual(compare_env_shapes(local, public), [])

    def test_status_mismatch_is_a_break(self):
        local = {"status": 200, "json": True, "kind": "object", "error": False, "row_keys": ["id"]}
        public = {"status": 500, "json": True, "kind": "object", "error": True, "row_keys": None}
        diffs = compare_env_shapes(local, public)
        self.assertTrue(any("status" in d for d in diffs))


if __name__ == "__main__":
    unittest.main()
