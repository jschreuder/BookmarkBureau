<?php declare(strict_types=1);

namespace Tests\Unit\Util;

use jschreuder\BookmarkBureau\Util\SqlExceptionHandler;
use PDOException;

describe("SqlExceptionHandler", function () {
    describe("isForeignKeyViolation", function () {
        test("detects SQLite foreign key constraint failure", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed",
            );
            expect(SqlExceptionHandler::isForeignKeyViolation($exception))->toBeTrue();
        });

        test("detects MySQL foreign key constraint failure", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`test`.`table`, CONSTRAINT `fk_dashboard_id` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`dashboard_id`))",
            );
            expect(SqlExceptionHandler::isForeignKeyViolation($exception))->toBeTrue();
        });

        test("detects case variations", function () {
            $exception = new PDOException("Foreign Key Constraint Failed");
            expect(SqlExceptionHandler::isForeignKeyViolation($exception))->toBeTrue();
        });

        test("returns false for non-FK errors", function () {
            $exception = new PDOException(
                "SQLSTATE[42S02]: Base table or view not found",
            );
            expect(SqlExceptionHandler::isForeignKeyViolation($exception))->toBeFalse();
        });

        test("returns false for duplicate entry errors", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'test' for key 'name'",
            );
            expect(SqlExceptionHandler::isForeignKeyViolation($exception))->toBeFalse();
        });
    });

    describe("isDuplicateEntry", function () {
        test("detects MySQL duplicate entry error", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'test-tag' for key 'name'",
            );
            expect(SqlExceptionHandler::isDuplicateEntry($exception))->toBeTrue();
        });

        test("detects SQLite unique constraint failure", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: tags.name",
            );
            expect(SqlExceptionHandler::isDuplicateEntry($exception))->toBeTrue();
        });

        test("detects case variations", function () {
            $exception = new PDOException("DUPLICATE ENTRY 'value'");
            expect(SqlExceptionHandler::isDuplicateEntry($exception))->toBeTrue();
        });

        test("returns false for non-duplicate errors", function () {
            $exception = new PDOException(
                "SQLSTATE[42S02]: Base table or view not found",
            );
            expect(SqlExceptionHandler::isDuplicateEntry($exception))->toBeFalse();
        });

        test("returns false for foreign key errors", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed",
            );
            expect(SqlExceptionHandler::isDuplicateEntry($exception))->toBeFalse();
        });
    });

    describe("getForeignKeyField", function () {
        test("extracts field from MySQL FK error with backticks", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`test`.`categories`, CONSTRAINT `fk_dashboard_id` FOREIGN KEY (`dashboard_id`) REFERENCES `dashboards` (`dashboard_id`))",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "dashboard_id",
            );
        });

        test("extracts link_id from error message", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`test`.`link_tags`, CONSTRAINT `fk_link_id` FOREIGN KEY (`link_id`) REFERENCES `links` (`link_id`))",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "link_id",
            );
        });

        test("extracts category_id from error message", function () {
            $exception = new PDOException(
                "FOREIGN KEY constraint failed on category_id",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "category_id",
            );
        });

        test("extracts tag_id from error message", function () {
            $exception = new PDOException(
                "Error with tag_id foreign key",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "tag_id",
            );
        });

        test("extracts user_id from error message", function () {
            $exception = new PDOException("FK violation on user_id");
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "user_id",
            );
        });

        test("handles case variations", function () {
            $exception = new PDOException("FK violation on DASHBOARD_ID");
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBe(
                "dashboard_id",
            );
        });

        test("returns null when no field can be determined", function () {
            $exception = new PDOException(
                "SQLSTATE[23000]: FOREIGN KEY constraint failed",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBeNull();
        });

        test("returns null for non-FK errors", function () {
            $exception = new PDOException(
                "SQLSTATE[42S02]: Base table or view not found",
            );
            expect(SqlExceptionHandler::getForeignKeyField($exception))->toBeNull();
        });
    });
});
