<?php

use jschreuder\BookmarkBureau\Entity\Mapper\LinkEntityMapper;
use jschreuder\BookmarkBureau\Entity\Mapper\DashboardEntityMapper;
use jschreuder\BookmarkBureau\Exception\RepositoryStorageException;
use jschreuder\BookmarkBureau\Util\SqlBuilder;

describe("SqlBuilder", function () {
    describe("selectFieldsFromMapper", function () {
        test(
            "returns unqualified fields when no table alias provided",
            function () {
                $mapper = new LinkEntityMapper();
                $result = SqlBuilder::selectFieldsFromMapper($mapper);

                expect($result)->toBe(
                    "link_id, url, title, description, icon, created_at, updated_at",
                );
            },
        );

        test(
            "returns unqualified fields when empty string table alias provided",
            function () {
                $mapper = new LinkEntityMapper();
                $result = SqlBuilder::selectFieldsFromMapper($mapper, "");

                expect($result)->toBe(
                    "link_id, url, title, description, icon, created_at, updated_at",
                );
            },
        );

        test("qualifies fields with table alias", function () {
            $mapper = new LinkEntityMapper();
            $result = SqlBuilder::selectFieldsFromMapper($mapper, "l");

            expect($result)->toBe(
                "l.link_id, l.url, l.title, l.description, l.icon, l.created_at, l.updated_at",
            );
        });

        test("applies field aliases to qualified fields", function () {
            $mapper = new LinkEntityMapper();
            $result = SqlBuilder::selectFieldsFromMapper($mapper, "l", [
                "created_at" => "createdAt",
                "updated_at" => "updatedAt",
            ]);

            expect($result)->toBe(
                "l.link_id, l.url, l.title, l.description, l.icon, l.created_at AS createdAt, l.updated_at AS updatedAt",
            );
        });

        test("applies field aliases to unqualified fields", function () {
            $mapper = new LinkEntityMapper();
            $result = SqlBuilder::selectFieldsFromMapper($mapper, null, [
                "created_at" => "createdAt",
            ]);

            expect($result)->toBe(
                "link_id, url, title, description, icon, created_at AS createdAt, updated_at",
            );
        });

        test("throws exception for non-existent field aliases", function () {
            $mapper = new LinkEntityMapper();

            expect(
                fn() => SqlBuilder::selectFieldsFromMapper($mapper, "l", [
                    "non_existent_field" => "alias",
                ]),
            )->toThrow(RepositoryStorageException::class);
        });

        test("works with different mapper implementations", function () {
            $mapper = new DashboardEntityMapper();
            $result = SqlBuilder::selectFieldsFromMapper($mapper, "d");

            expect($result)->toContain("d.dashboard_id");
            expect($result)->toContain("d.title");
            expect($result)->toContain("d.icon");
        });

        test("handles multiple field aliases", function () {
            $mapper = new LinkEntityMapper();
            $result = SqlBuilder::selectFieldsFromMapper($mapper, "links", [
                "link_id" => "id",
                "created_at" => "createdAt",
                "updated_at" => "updatedAt",
            ]);

            expect($result)->toBe(
                "links.link_id AS id, links.url, links.title, links.description, links.icon, links.created_at AS createdAt, links.updated_at AS updatedAt",
            );
        });
    });

    describe("buildSelect", function () {
        test("builds simple SELECT statement", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect("users", $fields);

            expect($result)->toBe("SELECT id, name FROM users");
        });

        test("adds WHERE clause when provided", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect("users", $fields, "active = 1");

            expect($result)->toBe(
                "SELECT id, name FROM users WHERE active = 1",
            );
        });

        test("adds ORDER BY clause when provided", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect(
                "users",
                $fields,
                null,
                "name ASC",
            );

            expect($result)->toBe(
                "SELECT id, name FROM users ORDER BY name ASC",
            );
        });

        test("adds WHERE and ORDER BY clauses", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect(
                "users",
                $fields,
                "active = 1",
                "name ASC",
            );

            expect($result)->toBe(
                "SELECT id, name FROM users WHERE active = 1 ORDER BY name ASC",
            );
        });

        test("adds LIMIT clause when provided", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect("users", $fields, null, null, 10);

            expect($result)->toBe("SELECT id, name FROM users LIMIT 10");
        });

        test("adds LIMIT and OFFSET clauses", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect(
                "users",
                $fields,
                null,
                null,
                10,
                5,
            );

            expect($result)->toBe(
                "SELECT id, name FROM users LIMIT 10 OFFSET 5",
            );
        });

        test("ignores OFFSET when LIMIT is not provided", function () {
            $fields = ["id", "name"];
            $result = SqlBuilder::buildSelect(
                "users",
                $fields,
                null,
                null,
                null,
                5,
            );

            expect($result)->toBe("SELECT id, name FROM users");
        });

        test("builds complete SELECT with all clauses", function () {
            $fields = ["id", "name", "email"];
            $result = SqlBuilder::buildSelect(
                "users",
                $fields,
                "active = 1",
                "created_at DESC",
                20,
                10,
            );

            expect($result)->toBe(
                "SELECT id, name, email FROM users WHERE active = 1 ORDER BY created_at DESC LIMIT 20 OFFSET 10",
            );
        });
    });

    describe("buildInsert", function () {
        test("builds INSERT with single field", function () {
            $row = ["name" => "John"];
            $result = SqlBuilder::buildInsert("users", $row);

            expect($result["sql"])->toContain("INSERT INTO users (name)");
            expect($result["sql"])->toContain("VALUES (:name)");
            expect($result["params"])->toBe([":name" => "John"]);
        });

        test("builds INSERT with multiple fields", function () {
            $row = ["id" => 1, "name" => "John", "email" => "john@example.com"];
            $result = SqlBuilder::buildInsert("users", $row);

            expect($result["sql"])->toContain("INSERT INTO users");
            expect($result["sql"])->toContain("(id, name, email)");
            expect($result["sql"])->toContain("VALUES (:id, :name, :email)");
            expect($result["params"])->toBe([
                ":id" => 1,
                ":name" => "John",
                ":email" => "john@example.com",
            ]);
        });

        test("preserves field order in parameters", function () {
            $row = ["email" => "test@example.com", "id" => 5, "name" => "Test"];
            $result = SqlBuilder::buildInsert("users", $row);

            expect(array_keys($result["params"]))->toBe([
                ":email",
                ":id",
                ":name",
            ]);
        });

        test("handles null values in INSERT", function () {
            $row = ["name" => "John", "phone" => null];
            $result = SqlBuilder::buildInsert("users", $row);

            expect($result["params"])->toBe([
                ":name" => "John",
                ":phone" => null,
            ]);
        });

        test("handles empty string values", function () {
            $row = ["name" => "", "description" => "test"];
            $result = SqlBuilder::buildInsert("users", $row);

            expect($result["params"][":name"])->toBe("");
        });

        test("handles special characters in values", function () {
            $row = ["name" => "O'Brien", "description" => 'Quote "test"'];
            $result = SqlBuilder::buildInsert("users", $row);

            expect($result["params"][":name"])->toBe("O'Brien");
            expect($result["params"][":description"])->toBe('Quote "test"');
        });
    });

    describe("buildUpdate", function () {
        test("builds UPDATE with single field", function () {
            $row = ["id" => 1, "name" => "Jane"];
            $result = SqlBuilder::buildUpdate("users", $row, "id");

            expect($result["sql"])->toContain("UPDATE users SET name = :name");
            expect($result["sql"])->toContain("WHERE id = :id");
            expect($result["params"])->toBe([":id" => 1, ":name" => "Jane"]);
        });

        test("excludes primary key from SET clause", function () {
            $row = ["id" => 1, "name" => "Jane", "email" => "jane@example.com"];
            $result = SqlBuilder::buildUpdate("users", $row, "id");

            expect($result["sql"])->toContain("UPDATE users SET");
            expect($result["sql"])->not()->toContain("id = :id WHERE");
            expect($result["sql"])->toContain("name = :name");
            expect($result["sql"])->toContain("email = :email");
            expect($result["sql"])->toContain("WHERE id = :id");
        });

        test(
            "includes primary key in parameters for WHERE clause",
            function () {
                $row = ["id" => 1, "name" => "Jane"];
                $result = SqlBuilder::buildUpdate("users", $row, "id");

                expect($result["params"])->toHaveKey(":id");
                expect($result["params"][":id"])->toBe(1);
            },
        );

        test("handles multiple fields in UPDATE", function () {
            $row = [
                "user_id" => 123,
                "name" => "John",
                "email" => "john@example.com",
                "phone" => "555-1234",
            ];
            $result = SqlBuilder::buildUpdate("users", $row, "user_id");

            expect($result["sql"])->toContain("name = :name");
            expect($result["sql"])->toContain("email = :email");
            expect($result["sql"])->toContain("phone = :phone");
            expect($result["sql"])->toContain("WHERE user_id = :user_id");
            expect($result["params"])->toHaveKey(":user_id");
            expect($result["params"])->toHaveKey(":name");
            expect($result["params"])->toHaveKey(":email");
            expect($result["params"])->toHaveKey(":phone");
        });

        test("handles null values in UPDATE", function () {
            $row = ["id" => 1, "name" => "Jane", "phone" => null];
            $result = SqlBuilder::buildUpdate("users", $row, "id");

            expect($result["params"][":phone"])->toBeNull();
        });

        test("generates proper SQL syntax", function () {
            $row = ["id" => 1, "name" => "Jane", "updated_at" => "2024-01-01"];
            $result = SqlBuilder::buildUpdate("users", $row, "id");

            expect($result["sql"])->toMatch(
                "/UPDATE users SET .* WHERE id = :id$/",
            );
        });

        test("handles different primary key fields", function () {
            $row = ["uuid" => "abc-123", "name" => "Test"];
            $result = SqlBuilder::buildUpdate("items", $row, "uuid");

            expect($result["sql"])->toContain("WHERE uuid = :uuid");
            expect($result["sql"])->toContain("name = :name");
            expect($result["params"][":uuid"])->toBe("abc-123");
        });
    });

    describe("buildDelete", function () {
        test("builds DELETE with single field", function () {
            $result = SqlBuilder::buildDelete("users", [
                "user_id" => "test123",
            ]);

            expect($result["sql"])->toBe(
                "DELETE FROM users WHERE user_id = :user_id",
            );
            expect($result["params"])->toBe([":user_id" => "test123"]);
        });

        test(
            "builds DELETE with multiple fields for junction tables",
            function () {
                $result = SqlBuilder::buildDelete("link_tags", [
                    "link_id" => "abc",
                    "tag_name" => "php",
                ]);

                expect($result["sql"])->toBe(
                    "DELETE FROM link_tags WHERE link_id = :link_id AND tag_name = :tag_name",
                );
                expect($result["params"])->toBe([
                    ":link_id" => "abc",
                    ":tag_name" => "php",
                ]);
            },
        );

        test("handles null values in WHERE clause", function () {
            $result = SqlBuilder::buildDelete("users", ["phone" => null]);

            expect($result["params"][":phone"])->toBeNull();
        });

        test("handles binary data in WHERE clause", function () {
            $binaryId = "binary-uuid-data";
            $result = SqlBuilder::buildDelete("users", [
                "user_id" => $binaryId,
            ]);

            expect($result["params"][":user_id"])->toBe($binaryId);
        });

        test("builds DELETE with three fields", function () {
            $result = SqlBuilder::buildDelete("complex_table", [
                "field1" => "value1",
                "field2" => "value2",
                "field3" => "value3",
            ]);

            expect($result["sql"])->toBe(
                "DELETE FROM complex_table WHERE field1 = :field1 AND field2 = :field2 AND field3 = :field3",
            );
            expect($result["params"])->toBe([
                ":field1" => "value1",
                ":field2" => "value2",
                ":field3" => "value3",
            ]);
        });

        test("preserves field order in parameters", function () {
            $result = SqlBuilder::buildDelete("users", [
                "email" => "test@example.com",
                "id" => 5,
                "status" => "active",
            ]);

            expect(array_keys($result["params"]))->toBe([
                ":email",
                ":id",
                ":status",
            ]);
        });
    });
});
