# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com) and this project adheres to [Semantic Versioning](https://semver.org).

## 3.0.0 - 2021-02-19

### Added

- Nothing

### Changed

- generateOtpCode() function now returns otp code and hash otp code in array

### Deprecated

- Nothing

### Removed

- Nothing

### Fixed

- Nothing

## 2.0.2 - 2021-02-01

### Added

- Nothing

### Changed

- Nothing

### Deprecated

- Nothing

### Removed

- Nothing

### Fixed

- Nothing

- Composer package keywords
- Use env variables in config file

## 2.0.1 - 2021-02-01

### Added

- Nothing

### Changed

- Nothing

### Deprecated

- Nothing

### Removed

- Nothing

### Fixed

- Composer package description

## 2.0.0 - 2021-01-31

### Added

- Nothing

### Changed

- use a single function generateOtpCode() to generate otp
- added updateRow parameter to createOtp() function. If true, update row if it already exists. If false, create a new row everytime.

### Deprecated

- hashOtpCode() function
- createOrUpdateOtp() function
- sendOtp() function

### Removed

- hashOtpCode() function. Now hash is done by generateOtpCode() function
- createOrUpdateOtp() function
- send otp functionality as it was tied to sms. We can use a different package to send otp via any channel like sms, email, etc..
- config related to send otp communication class

### Fixed

- Nothing

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