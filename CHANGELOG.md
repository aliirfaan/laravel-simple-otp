# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com) and this project adheres to [Semantic Versioning](https://semver.org).

## 1.1.0 - 2021-01-31

### Added

- Added a createOrUpdateOtp function in ModelGotOtp model so that row is updated if it exists. Helps in reducing database size and housekeeping tasks in having to delete old rows.

### Changed

- Updated changelog format

### Deprecated

- Nothing

### Removed

- Nothing

### Fixed

- added App\ in class path in otp config to have full path. Example: App\Services\ExampleOtpProviderService::class