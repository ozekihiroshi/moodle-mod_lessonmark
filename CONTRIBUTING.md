# Contributing to LessonMark

Thank you for helping improve LessonMark.

## Before opening a change

- Follow the shared development principles ([English](docs/DEVELOPMENT_PRINCIPLES.md) /
  [日本語](docs/DEVELOPMENT_PRINCIPLES.ja.md)):
  LessonMark, the Dual Learning course format, and the Dual Learning theme must
  remain independently usable, with optional cooperation and clear ownership.
  Explain the responsible component and verify the combinations affected by a change.
- Use GitHub Issues for reproducible non-security defects and focused feature
  proposals.
- Use the private process in `SECURITY.md` for vulnerabilities.
- Keep Markdown source as the stored source of truth.
- Reuse Moodle APIs for capabilities, files, privacy, backup, restore, forms,
  and output.
- Do not add a runtime network service or third-party dependency without
  documenting its license, provenance, security boundary, and reconstruction.

## Development checks

Development is performed in WSL. From the repository root:

```sh
scripts/run-ci-local.sh
scripts/build-release.sh
php scripts/verify-release.php 0.1.0 build/mod_lessonmark.zip
```

The local CI runner creates temporary containers and runs the Moodle PHP,
JavaScript, CSS, PHPUnit, packaging, security, and privacy gates. Pull requests
must keep GitHub Actions green and include tests appropriate to the change.

## License

By submitting a contribution, you agree that it is licensed under the GNU GPL
v3 or later, matching LessonMark.
