
use PHPUnit\Framework\TestCase;
use App\Services\BcaiAdapter;
class BcaiAdapterTest extends TestCase
{
public function testSummarizeReturnsMockShape()
{
    $a = new BcaiAdapter();
    $out = $a->summarize("This is a fake email body for unit tests.");
    $this->assertArrayHasKey('summary', $out);
    $this->assertArrayHasKey('priority_hint', $out);
    $this->assertArrayHasKey('actions', $out);
    $this->assertArrayHasKey('confidence', $out);
}
public function testClassifyPriorityReturnsShape()
{
    $a = new BcaiAdapter();
    $out = $a->classifyPriority("Another fake email.");
    $this->assertArrayHasKey('priority', $out);
    $this->assertArrayHasKey('reasons', $out);
}
}
