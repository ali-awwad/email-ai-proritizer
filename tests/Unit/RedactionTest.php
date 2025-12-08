
use PHPUnit\Framework\TestCase;
use App\Services\Redaction;
class RedactionTest extends TestCase
{
public function testRedactsEmailAndPhone()
{
    $r = new Redaction();
    $sample = "Contact me at alice@example.com or 555-123-4567. Account 12345678.";
    $result = $r->redact($sample);
    $this->assertStringNotContainsString('alice@example.com', $result['redacted_text']);
    $this->assertStringNotContainsString('555-123-4567', $result['redacted_text']);
    $this->assertStringContainsString('[REDACTED-EMAIL]', $result['redacted_text']);
    $this->assertStringContainsString('[REDACTED-PHONE]', $result['redacted_text']);
    $this->assertNotEmpty($result['redactions']);
}
}
