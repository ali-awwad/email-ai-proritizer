Purpose: Add a BCAI adapter and conservative redaction layer to enable safe AI-driven email triage.
Safety rules:
Use only synthetic (non-sensitive) emails for testing.
Never send raw Boeing email or PII to any external model.
All model calls must be preceded by the redaction preprocessor and recorded in the audit log.
Deliverables:
app/Services/BcaiAdapter.php — skeleton adapter (mocked for unit tests).
app/Services/Redaction.php — deterministic regex-based redaction that returns redacted_text + redactions[].
tests/Unit/* — unit tests for redaction and adapter (mocked).
.github/workflows/ci.yml — CI job to run tests on GitHub Actions (ubuntu runner, install PHP, run phpunit).
