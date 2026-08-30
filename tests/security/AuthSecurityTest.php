<?php

use PHPUnit\Framework\TestCase;

/**
 * XSS / input-poilcy tests for the sign-in and sign-up validation rules.
 *
 * The rules live in application/config/form_rules.php. This test loads that
 * config and verifies the regex_match patterns actually reject HTML/script
 * payloads, spaces, symbols, and emojis while accepting safe usernames.
 */
class AuthSecurityTest extends TestCase {

    private $rules;

    protected function setUp(): void {
        $config = [];
        require APPPATH . 'config/form_rules.php';
        $this->rules = $config;
    }

    /**
     * Extract the {field} validation rules array from a named rule set.
     */
    private function rulesFor(string $set): array {
        $this->assertArrayHasKey($set, $this->rules, "Rule set '{$set}' missing");
        return $this->rules[$set];
    }

    /**
     * Pull the regex_match pattern from a field's rules string.
     *
     * Each rule set is split on '|'; the token beginning with "regex_match["
     * holds the pattern embedded as regex_match[/^...$/]. We strip the
     * wrapper and wrap the pattern with '~' delimiters for preg_match().
     */
    private function regexFor(array $fieldRules, string $field): ?string {
        foreach ($fieldRules as $rule) {
            if ($rule['field'] !== $field) {
                continue;
            }
            foreach (explode('|', $rule['rules']) as $token) {
                if (strpos($token, 'regex_match[') === 0) {
                    $inner = substr($token, strlen('regex_match['));
                    $inner = rtrim($inner, ']');
                    // CI3 stores the pattern delimited by slashes:
                    // regex_match[/^...$/]. Strip the leading '/' and a trailing
                    // '/', keeping the pattern (including the $ anchor) intact.
                    if (isset($inner[0]) && $inner[0] === '/' && substr($inner, -1) === '/') {
                        $inner = substr($inner, 1, -1);
                    }
                    return '~' . $inner . '~';
                }
            }
            $this->fail("No regex_match rule found for field '{$field}'");
        }
        $this->fail("No rules found for field '{$field}'");
    }

    public function testSignupUsernameRejectsScriptAndHtml() {
        $re = $this->regexFor($this->rulesFor('user_signup'), 'username');
        foreach (['<script>alert(1)</script>', '<b>hi</b>', '"><img src=x onerror=alert(1)>'] as $payload) {
            $this->assertSame(0, preg_match($re, $payload), "Should reject: {$payload}");
        }
    }

    public function testSignupUsernameRejectsSpacesSymbolsAndEmojis() {
        $re = $this->regexFor($this->rulesFor('user_signup'), 'username');
        foreach (['user name', 'user#name', 'user$name', 'user😀', "user'name", 'user"name'] as $payload) {
            $this->assertSame(0, preg_match($re, $payload), "Should reject: {$payload}");
        }
    }

    public function testSignupUsernameAcceptsSafeChars() {
        $re = $this->regexFor($this->rulesFor('user_signup'), 'username');
        foreach (['alice', 'user_name-9@x.com', 'Bob_2024'] as $payload) {
            $this->assertSame(1, preg_match($re, $payload), "Should accept: {$payload}");
        }
    }

    public function testSigninUsernameRejectsScriptAndHtml() {
        $re = $this->regexFor($this->rulesFor('user_signin'), 'username');
        foreach (['<script>alert(1)</script>', '<b>hi</b>', '"><img src=x onerror=alert(1)>'] as $payload) {
            $this->assertSame(0, preg_match($re, $payload), "Should reject: {$payload}");
        }
    }

    public function testSigninUsernameRejectsSpacesSymbolsAndEmojis() {
        $re = $this->regexFor($this->rulesFor('user_signin'), 'username');
        foreach (['user name', 'user#name', 'user😀', "user'name"] as $payload) {
            $this->assertSame(0, preg_match($re, $payload), "Should reject: {$payload}");
        }
    }

    public function testSigninUsernameAcceptsSafeChars() {
        $re = $this->regexFor($this->rulesFor('user_signin'), 'username');
        foreach (['alice', 'user_name-9@x.com'] as $payload) {
            $this->assertSame(1, preg_match($re, $payload), "Should accept: {$payload}");
        }
    }

    public function testNameFieldsRejectHtml() {
        foreach (['firstname', 'lastname'] as $field) {
            $re = $this->regexFor($this->rulesFor('user_signup'), $field);
            $this->assertSame(0, preg_match($re, '<b>hi</b>'), "{$field} should reject HTML");
            $this->assertSame(1, preg_match($re, 'John'), "{$field} should accept plain text");
        }
    }
}
