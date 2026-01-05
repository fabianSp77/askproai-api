<?php

namespace Tests\Unit\ServiceGateway;

use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use Tests\TestCase;
use ReflectionClass;

/**
 * SSRF Protection Tests for WebhookOutputHandler
 *
 * Tests the hardened isExternalUrl() implementation against all known SSRF bypass vectors.
 * Covers: localhost variants, alternative IP notations, IPv6, IPv4-mapped IPv6, private ranges,
 * cloud metadata endpoints, dangerous protocols, and port restrictions.
 *
 * @package Tests\Unit\ServiceGateway
 */
class WebhookOutputHandlerSsrfTest extends TestCase
{
    private WebhookOutputHandler $handler;
    private \ReflectionMethod $isExternalUrlMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new WebhookOutputHandler();

        $reflection = new ReflectionClass($this->handler);
        $this->isExternalUrlMethod = $reflection->getMethod('isExternalUrl');
        $this->isExternalUrlMethod->setAccessible(true);
    }

    private function assertBlocks(string $url, string $reason = ''): void
    {
        $result = $this->isExternalUrlMethod->invoke($this->handler, $url);
        $this->assertFalse($result, "URL should be BLOCKED ($reason): $url");
    }

    private function assertAllows(string $url, string $reason = ''): void
    {
        $result = $this->isExternalUrlMethod->invoke($this->handler, $url);
        $this->assertTrue($result, "URL should be ALLOWED ($reason): $url");
    }

    // =========================================================================
    // SSRF-001: Localhost Variants
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_localhost_variants(): void
    {
        $this->assertBlocks('http://localhost/', 'basic localhost');
        $this->assertBlocks('http://localhost:80/', 'localhost with port');
        $this->assertBlocks('http://LOCALHOST/', 'uppercase localhost');
        $this->assertBlocks('http://Localhost/', 'mixed case localhost');
        $this->assertBlocks('http://localhost.localdomain/', 'localhost.localdomain');
    }

    // =========================================================================
    // SSRF-002: Loopback IP Addresses
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_loopback_ip_addresses(): void
    {
        $this->assertBlocks('http://127.0.0.1/', 'loopback 127.0.0.1');
        $this->assertBlocks('http://127.0.0.1:80/', 'loopback with port');
        $this->assertBlocks('http://127.1/', 'loopback shorthand 127.1');
        $this->assertBlocks('http://127.0.1/', 'loopback shorthand 127.0.1');
    }

    // =========================================================================
    // SSRF-003: Alternative IP Notation (Critical!)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_decimal_ip_notation(): void
    {
        // 2130706433 = 127.0.0.1 in decimal
        $this->assertBlocks('http://2130706433/', 'decimal localhost');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_octal_ip_notation(): void
    {
        // 0177 = 127 in octal
        $this->assertBlocks('http://0177.0.0.1/', 'octal localhost');
        $this->assertBlocks('http://017700000001/', 'octal full notation');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_hexadecimal_ip_notation(): void
    {
        // 0x7f = 127 in hex
        $this->assertBlocks('http://0x7f000001/', 'hex localhost');
        $this->assertBlocks('http://0x7f.0.0.1/', 'hex first octet');
        $this->assertBlocks('http://0x7f.0x0.0x0.0x1/', 'all hex octets');
    }

    // =========================================================================
    // SSRF-004: IPv6 Addresses
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_ipv6_loopback(): void
    {
        $this->assertBlocks('http://[::1]/', 'IPv6 loopback ::1');
        $this->assertBlocks('http://[0:0:0:0:0:0:0:1]/', 'IPv6 loopback full');
        $this->assertBlocks('http://ip6-localhost/', 'ip6-localhost');
        $this->assertBlocks('http://ip6-loopback/', 'ip6-loopback');
    }

    // =========================================================================
    // SSRF-005: IPv4-mapped IPv6 (Critical!)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_ipv4_mapped_ipv6_loopback(): void
    {
        $this->assertBlocks('http://[::ffff:127.0.0.1]/', 'IPv4-mapped loopback');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_ipv4_mapped_ipv6_private(): void
    {
        $this->assertBlocks('http://[::ffff:192.168.1.1]/', 'IPv4-mapped 192.168');
        $this->assertBlocks('http://[::ffff:10.0.0.1]/', 'IPv4-mapped 10.x');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_ipv4_mapped_ipv6_metadata(): void
    {
        $this->assertBlocks('http://[::ffff:169.254.169.254]/', 'IPv4-mapped AWS metadata');
    }

    // =========================================================================
    // SSRF-006: Private IP Ranges (RFC 1918)
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_192_168_range(): void
    {
        $this->assertBlocks('http://192.168.1.1/', 'private 192.168.1.x');
        $this->assertBlocks('http://192.168.0.1/', 'private 192.168.0.x');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_10_x_range(): void
    {
        $this->assertBlocks('http://10.0.0.1/', 'private 10.0.0.x');
        $this->assertBlocks('http://10.255.255.255/', 'private 10.x end');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_172_16_range(): void
    {
        $this->assertBlocks('http://172.16.0.1/', 'private 172.16.x start');
        $this->assertBlocks('http://172.31.255.255/', 'private 172.31.x end');
    }

    // =========================================================================
    // SSRF-007: Cloud Metadata Endpoints
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_aws_metadata_endpoint(): void
    {
        $this->assertBlocks('http://169.254.169.254/', 'AWS metadata IP');
        $this->assertBlocks('http://169.254.169.254/latest/meta-data/', 'AWS metadata path');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_gcp_metadata_endpoint(): void
    {
        $this->assertBlocks('http://metadata.google.internal/', 'GCP metadata');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_azure_metadata_endpoint(): void
    {
        $this->assertBlocks('http://instance-data/', 'Azure metadata');
    }

    // =========================================================================
    // SSRF-008: Kubernetes Internal
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_kubernetes_internal(): void
    {
        $this->assertBlocks('http://kubernetes.default/', 'kubernetes.default');
        $this->assertBlocks('http://kubernetes.default.svc/', 'kubernetes.default.svc');
    }

    // =========================================================================
    // SSRF-009: Reserved IP Addresses
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_reserved_ip_addresses(): void
    {
        $this->assertBlocks('http://0.0.0.0/', 'zero IP');
        $this->assertBlocks('http://0/', 'zero shorthand');
        $this->assertBlocks('http://255.255.255.255/', 'broadcast');
    }

    // =========================================================================
    // SSRF-010: Dangerous Protocols
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_dangerous_protocols(): void
    {
        $this->assertBlocks('gopher://localhost:6379/', 'gopher protocol');
        $this->assertBlocks('file:///etc/passwd', 'file protocol');
        $this->assertBlocks('ftp://localhost/', 'ftp protocol');
        $this->assertBlocks('dict://localhost/', 'dict protocol');
        $this->assertBlocks('ldap://localhost/', 'ldap protocol');
    }

    // =========================================================================
    // SSRF-011: Non-Standard Ports
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_non_standard_ports(): void
    {
        $this->assertBlocks('https://example.com:6379/', 'Redis port');
        $this->assertBlocks('https://example.com:3306/', 'MySQL port');
        $this->assertBlocks('https://example.com:5432/', 'PostgreSQL port');
        $this->assertBlocks('https://example.com:22/', 'SSH port');
        $this->assertBlocks('https://example.com:8080/', 'Alt HTTP port');
    }

    // =========================================================================
    // SSRF-012: URL Obfuscation
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_url_obfuscation(): void
    {
        $this->assertBlocks("http://127.0.0.1%00.example.com/", 'null byte injection');
        $this->assertBlocks('http://localhost%40example.com/', 'encoded @ symbol');
    }

    // =========================================================================
    // ALLOW: Valid External URLs
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_public_https_domains(): void
    {
        $this->assertAllows('https://example.com/', 'example.com');
        $this->assertAllows('https://example.com:443/', 'example.com explicit 443');
        $this->assertAllows('https://hooks.slack.com/services/xxx', 'Slack webhook');
        $this->assertAllows('https://webhook.site/test', 'webhook.site');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_public_ip_addresses(): void
    {
        $this->assertAllows('https://8.8.8.8/', 'Google DNS');
        $this->assertAllows('https://1.1.1.1/', 'Cloudflare DNS');
        $this->assertAllows('https://208.67.222.222/', 'OpenDNS');
    }

    // =========================================================================
    // Production Mode: HTTPS Only
    // =========================================================================

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_blocks_http_in_production(): void
    {
        // This test checks that HTTP is blocked in production
        // In non-production, HTTP is allowed for testing
        if (app()->isProduction()) {
            $this->assertBlocks('http://example.com/', 'HTTP in production');
        } else {
            // In non-production, HTTP is allowed for external hosts
            // But internal hosts are still blocked
            $this->assertBlocks('http://localhost/', 'localhost even with HTTP');
        }
    }
}
