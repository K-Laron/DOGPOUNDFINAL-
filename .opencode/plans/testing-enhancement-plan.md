# Catarman Dog Pound - Testing Enhancement Plan

## Overview

This plan outlines the comprehensive testing enhancement for the backend system without changing the tech stack. The goal is to achieve full backend test coverage.

## Current State

- **5 existing test files** (3 unit, 2 feature)
- **~60 tests** currently
- **PHPUnit 10** configured with test suites for Unit and Feature tests

### Existing Tests
| File | Type | Status |
|------|------|--------|
| `ValidatorTest.php` | Unit | Complete |
| `SanitizerTest.php` | Unit | Complete |
| `JWTTest.php` | Unit | Complete |
| `AnimalsTest.php` | Feature | Complete |
| `AuthTest.php` | Feature | Complete |

---

## Implementation Plan

### Phase 1: Unit Tests for Utilities (5 files)

#### 1.1 FeeCalculatorTest.php
**Location:** `backend/tests/Unit/Utils/FeeCalculatorTest.php`

**Test Cases (~20 tests):**
- `testGetConfigReturnsDefaultFees`
- `testConfigValuesArePositive`
- `testCalculateAdoptionFeeReturnsErrorForNonexistentAnimal`
- `testCalculateAdoptionFeeBasicAnimal`
- `testCalculateAdoptionFeeWithSpayNeuter`
- `testCalculateAdoptionFeeWithVaccinations`
- `testCalculateAdoptionFeeWithTreatmentDiscount`
- `testCalculateAdoptionFeeFullPackage`
- `testCalculateAdoptionFeeNeverNegative`
- `testCalculateReclaimFeeReturnsErrorForNonexistentAnimal`
- `testCalculateReclaimFeeWithImpoundRecord`
- `testCalculateReclaimFeeWithoutImpoundRecord`
- `testCalculateReclaimFeeMinimumOneDay`
- `testCalculateFeeRoutesToAdoption`
- `testCalculateFeeRoutesToReclaim`
- `testCalculateFeeInvalidTypeReturnsError`
- `testBreakdownItemsHaveCorrectStructure`

**Mocking Strategy:** Mock PDO and PDOStatement to simulate database responses

---

#### 1.2 RateLimiterTest.php
**Location:** `backend/tests/Unit/Utils/RateLimiterTest.php`

**Test Cases (~15 tests):**
- `testCheckAllowsRequestUnderLimit`
- `testCheckBlocksRequestOverLimit`
- `testCheckCleansOldEntriesOutsideWindow`
- `testCheckDisabledWhenRateLimitEnabledFalse`
- `testCheckGlobalUsesDefaultLimits`
- `testCheckLoginUsesLoginLimits`
- `testGetRemainingReturnsCorrectCount`
- `testResetClearsRateLimitData`
- `testCleanupRemovesExpiredFiles`
- `testGenerateKeyHandlesSpecialCharacters`
- `testGenerateKeyHashesLongIdentifiers`
- `testGetClientIPReturnsRemoteAddr`
- `testGetClientIPTrustsProxyWhenConfigured`
- `testSendRateLimitResponseSetsHeaders`
- `testStorageDirectoryCreatedIfNotExists`

**Mocking Strategy:** Use temp directory for file-based storage, mock $_SERVER for IP tests

---

#### 1.3 ResponseTest.php
**Location:** `backend/tests/Unit/Utils/ResponseTest.php`

**Test Cases (~15 tests):**
- `testSuccessReturnsCorrectStructure`
- `testSuccessIncludesDataWhenProvided`
- `testSuccessExcludesDataWhenNull`
- `testErrorReturnsCorrectStructure`
- `testErrorIncludesErrorsWhenProvided`
- `testErrorIncludesDebugInfoInDevelopment`
- `testPaginatedReturnsCorrectStructure`
- `testPaginatedCalculatesTotalPagesCorrectly`
- `testPaginatedHasNextAndPrevFlags`
- `testCreatedReturns201StatusCode`
- `testNoContentReturns204StatusCode`
- `testValidationErrorReturns422`
- `testUnauthorizedReturns401`
- `testForbiddenReturns403`
- `testNotFoundReturns404`
- `testMethodNotAllowedReturns405`
- `testConflictReturns409`
- `testServerErrorReturns500`

**Note:** These tests require output buffering to capture JSON output

---

#### 1.4 RouterTest.php
**Location:** `backend/tests/Unit/Utils/RouterTest.php`

**Test Cases (~12 tests):**
- `testGetRegistersGetRoute`
- `testPostRegistersPostRoute`
- `testPutRegistersPutRoute`
- `testDeleteRegistersDeleteRoute`
- `testPatchRegistersPatchRoute`
- `testRoutePatternsConvertParametersToRegex`
- `testGetRoutesReturnsAllRegisteredRoutes`
- `testRoutePathsPrependedWithApiVersion`
- `testDispatchMatchesCorrectRoute`
- `testDispatchReturns404ForUnmatchedRoute`
- `testAuthenticationRequiredWhenRolesSpecified`
- `testAuthorizationCheckRoles`

**Mocking Strategy:** Mock PDO, set $_SERVER variables for request simulation

---

#### 1.5 EnvTest.php
**Location:** `backend/tests/Unit/Utils/EnvTest.php`

**Test Cases (~12 tests):**
- `testLoadReturnsFalseForMissingFile`
- `testLoadReturnsTrueForExistingFile`
- `testLoadParsesKeyValuePairs`
- `testLoadSkipsComments`
- `testLoadSkipsEmptyLines`
- `testLoadRemovesSurroundingQuotes`
- `testGetReturnsValueWhenSet`
- `testGetReturnsDefaultWhenNotSet`
- `testGetConvertsTrueStringToBoolean`
- `testGetConvertsFalseStringToBoolean`
- `testGetConvertsNullStringToNull`
- `testHasReturnsTrueWhenSet`
- `testHasReturnsFalseWhenNotSet`
- `testRequireThrowsExceptionWhenMissing`
- `testRequireReturnsValueWhenSet`

**Mocking Strategy:** Create temporary .env files for testing

---

### Phase 2: Unit Tests for Models (12 files)

#### 2.1 AnimalModelTest.php
**Location:** `backend/tests/Unit/Models/AnimalModelTest.php`

**Test Cases (~25 tests):**
- `testFindReturnsAnimalById`
- `testFindReturnsFalseForDeletedAnimal`
- `testFindReturnsFalseForNonexistentAnimal`
- `testFindWithRelationsIncludesImpoundRecord`
- `testFindWithRelationsIncludesCounts`
- `testPaginateReturnsDataAndTotal`
- `testPaginateAppliesTypeFilter`
- `testPaginateAppliesStatusFilter`
- `testPaginateAppliesSearchFilter`
- `testPaginateAppliesDateRangeFilter`
- `testPaginateAppliesSorting`
- `testGetAvailableFiltersToAvailableStatus`
- `testCreateInsertsNewAnimal`
- `testCreateReturnsAnimalId`
- `testCreateReturnsFalseOnFailure`
- `testUpdateModifiesAnimalFields`
- `testUpdateIgnoresUnallowedFields`
- `testUpdateReturnsFalseWhenNoFields`
- `testDeleteSoftDeletesAnimal`
- `testUpdateStatusChangesStatus`
- `testUpdateImageSetsImagePath`
- `testGetStatisticsReturnsAllCounts`
- `testGetMonthlyIntakeStatsReturnsMonthlyData`
- `testGetByStatusFiltersCorrectly`
- `testSearchMatchesNameAndBreed`

---

#### 2.2 UserModelTest.php
**Location:** `backend/tests/Unit/Models/UserModelTest.php`

**Test Cases (~20 tests):**
- `testFindReturnsUserById`
- `testFindByEmailReturnsUser`
- `testFindByUsernameReturnsUser`
- `testCreateInsertsNewUser`
- `testCreateHashesPassword`
- `testVerifyPasswordReturnsTrueForCorrectPassword`
- `testVerifyPasswordReturnsFalseForWrongPassword`
- `testUpdateProfileModifiesUserFields`
- `testUpdatePasswordHashesNewPassword`
- `testDeleteSoftDeletesUser`
- `testEmailExistsReturnsTrueWhenExists`
- `testEmailExistsReturnsFalseWhenNotExists`
- `testUsernameExistsReturnsTrueWhenExists`
- `testGetByRoleFiltersCorrectly`
- `testPaginateAppliesFilters`
- `testUpdateAvatarSetsPath`
- `testUpdateStatusChangesAccountStatus`
- `testGetStatisticsReturnsUserCounts`

---

#### 2.3 - 2.12 Other Models
Similar test structure for:
- AdoptionRequestModelTest.php
- MedicalRecordModelTest.php
- InventoryModelTest.php
- InvoiceModelTest.php
- PaymentModelTest.php
- ImpoundRecordModelTest.php
- FeedingRecordModelTest.php
- VeterinarianModelTest.php
- ActivityLogModelTest.php
- RoleModelTest.php

---

### Phase 3: Unit Tests for Middleware (2 files)

#### 3.1 AuthMiddlewareTest.php
**Location:** `backend/tests/Unit/Middleware/AuthMiddlewareTest.php`

**Test Cases (~20 tests):**
- `testAuthenticateRequiresToken`
- `testAuthenticateRejectsInvalidToken`
- `testAuthenticateRejectsExpiredToken`
- `testAuthenticateSetsUserOnSuccess`
- `testAuthenticateRejectsInactiveAccount`
- `testRequireRoleAllowsMatchingRole`
- `testRequireRoleRejectsNonMatchingRole`
- `testRequireRoleAcceptsWildcard`
- `testHasRoleReturnsTrueForMatchingRole`
- `testHasRoleReturnsFalseForNonMatchingRole`
- `testIsAdminReturnsTrueForAdmin`
- `testIsAdminReturnsFalseForNonAdmin`
- `testIsStaffReturnsTrueForStaffAndAdmin`
- `testIsOwnerReturnsTrueForMatchingUserId`
- `testIsOwnerReturnsFalseForDifferentUserId`
- `testCanAccessAllowsOwner`
- `testCanAccessAllowsAdminRole`
- `testOptionalAuthReturnsNullWithoutToken`
- `testOptionalAuthReturnsUserWithValidToken`
- `testRequireOwnerOrRoleAllowsOwner`
- `testRequireOwnerOrRoleAllowsRole`

---

#### 3.2 RequestLoggerTest.php
**Location:** `backend/tests/Unit/Middleware/RequestLoggerTest.php`

**Test Cases (~8 tests):**
- `testLogCreatesLogEntry`
- `testLogCapturesMethod`
- `testLogCapturesPath`
- `testLogCapturesUserId`
- `testLogCapturesIPAddress`
- `testLogCapturesTimestamp`
- `testLogHandlesMissingUser`
- `testLogFormatsCorrectly`

---

### Phase 4: Feature Tests for Controllers (8 files)

#### 4.1 UsersTest.php
**Location:** `backend/tests/Feature/UsersTest.php`

**Test Cases (~18 tests):**
- `testListUsersRequiresAuth`
- `testListUsersReturnsUsers`
- `testListUsersSupportsPagination`
- `testListUsersFiltersbyRole`
- `testGetUserReturnsUserDetails`
- `testGetUserReturns404ForNonexistent`
- `testCreateUserSucceeds`
- `testCreateUserValidatesRequiredFields`
- `testCreateUserValidatesEmailFormat`
- `testCreateUserRejectsExistingEmail`
- `testUpdateUserModifiesFields`
- `testUpdateUserOwnProfile`
- `testUpdateUserAdminCanUpdateAny`
- `testDeleteUserRequiresAdmin`
- `testDeleteUserSoftDeletes`
- `testUploadAvatarSucceeds`
- `testUploadAvatarValidatesFileType`
- `testChangePasswordRequiresOldPassword`

---

#### 4.2 - 4.8 Other Feature Tests
Similar structure for:
- AdoptionsTest.php (adoption workflow, status changes)
- InventoryTest.php (stock management, alerts)
- MedicalTest.php (records, scheduling)
- BillingTest.php (invoices, payments)
- DashboardTest.php (statistics, summaries)
- NotificationsTest.php (CRUD, mark read)
- SystemTest.php (health check, info)

---

### Phase 5: Test Infrastructure

#### 5.1 Base TestCase
**Location:** `backend/tests/TestCase.php`

```php
<?php
namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static $baseUrl = 'http://localhost:8000/api/v1';
    protected static $accessToken;
    
    protected function makeRequest($method, $endpoint, $data = null, $token = null): array
    {
        // HTTP request helper
    }
    
    protected function createMockPdo(): \PDO
    {
        // Create mock PDO
    }
    
    protected function createMockStatement($returnValue): \PDOStatement
    {
        // Create mock PDOStatement
    }
}
```

#### 5.2 MockPDO Helper
**Location:** `backend/tests/Fixtures/MockPDO.php`

Provides reusable mock database setup for unit tests.

#### 5.3 Test Traits
**Location:** `backend/tests/Traits/`

- `ApiTestTrait.php` - HTTP request helpers
- `MockDatabaseTrait.php` - Database mocking helpers

---

## Directory Structure After Implementation

```
backend/tests/
├── bootstrap.php
├── TestCase.php
├── Unit/
│   ├── ValidatorTest.php
│   ├── SanitizerTest.php
│   ├── JWTTest.php
│   ├── Utils/
│   │   ├── FeeCalculatorTest.php
│   │   ├── RateLimiterTest.php
│   │   ├── ResponseTest.php
│   │   ├── RouterTest.php
│   │   └── EnvTest.php
│   ├── Models/
│   │   ├── AnimalModelTest.php
│   │   ├── UserModelTest.php
│   │   ├── AdoptionRequestModelTest.php
│   │   ├── MedicalRecordModelTest.php
│   │   ├── InventoryModelTest.php
│   │   ├── InvoiceModelTest.php
│   │   ├── PaymentModelTest.php
│   │   ├── ImpoundRecordModelTest.php
│   │   ├── FeedingRecordModelTest.php
│   │   ├── VeterinarianModelTest.php
│   │   ├── ActivityLogModelTest.php
│   │   └── RoleModelTest.php
│   └── Middleware/
│       ├── AuthMiddlewareTest.php
│       └── RequestLoggerTest.php
├── Feature/
│   ├── AnimalsTest.php
│   ├── AuthTest.php
│   ├── UsersTest.php
│   ├── AdoptionsTest.php
│   ├── InventoryTest.php
│   ├── MedicalTest.php
│   ├── BillingTest.php
│   ├── DashboardTest.php
│   ├── NotificationsTest.php
│   └── SystemTest.php
├── Fixtures/
│   ├── MockPDO.php
│   ├── UserFixture.php
│   └── AnimalFixture.php
└── Traits/
    ├── ApiTestTrait.php
    └── MockDatabaseTrait.php
```

---

## Test Count Estimate

| Phase | New Files | New Tests |
|-------|-----------|-----------|
| Phase 1: Utilities | 5 | ~75 |
| Phase 2: Models | 12 | ~150 |
| Phase 3: Middleware | 2 | ~28 |
| Phase 4: Feature | 8 | ~100 |
| Phase 5: Infrastructure | 4 | N/A |
| **Total** | **31** | **~353** |

Combined with existing 60 tests: **~413 total tests**

---

## Implementation Order

Execute in this order to maximize value and minimize dependencies:

1. **FeeCalculatorTest** - Critical business logic
2. **RateLimiterTest** - Security-critical
3. **ResponseTest** - Foundation for feature tests
4. **EnvTest** - Simple utility
5. **RouterTest** - Core routing
6. **AuthMiddlewareTest** - Security
7. **AnimalModelTest** - Core entity
8. **UserModelTest** - Core entity
9. **Remaining model tests**
10. **Feature tests** (Users, Adoptions, etc.)
11. **Test infrastructure** (base classes, fixtures)

---

## Running Tests

After implementation:

```bash
# Run all tests
cd backend
./vendor/bin/phpunit

# Run specific suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

---

## Success Criteria

- All new tests pass
- No regressions in existing tests
- Test execution time < 60 seconds
- Code coverage > 80% for tested files
- Feature tests validate critical workflows

---

## Notes

- All tests follow existing patterns from ValidatorTest.php and AnimalsTest.php
- Mock PDO used for unit tests to avoid database dependencies
- Feature tests require running server on localhost:8000
- PHPUnit 10 compatible with strict types
