
namespace App\Services;

class BcaiAdapter

{

// summarize: accepts redacted text, returns fixed mocked response for unit tests
public function summarize(string $text): array
{
    return [
        'summary' => '[MOCK] This is a mock summary.',
        'priority_hint' => 'Low',
        'actions' => ['Archive'],
        'sensitivity_flag' => false,
        'confidence' => 0.99,
    ];
}
// classifyPriority: mocked priority classification
public function classifyPriority(string $text): array
{
    return [
        'priority' => 'Low',
        'reasons' => ['No urgent items detected'],
        'confidence' => 0.9,
    ];
}
// embed: mocked embedding (returns an empty vector for tests)
publtion, redaction test, adapter test, or ci.yml)
References
