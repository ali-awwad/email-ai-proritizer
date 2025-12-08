
namespace App\Services;
class Redaction
{
// Very simple, conservative redaction for unit tests and prototyping.
// Replace emails, phone numbers, and long digit sequences with tokens.
public function redact(string $text): array
{
    $redactions = [];
    // email
    $text = preg_replace_callback('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', function($m) use (&$redactions) {
        $redactions[] = ['type'=>'email','value'=>$m[0]];
        return '[REDACTED-EMAIL]';
    }, $text);
    // phone (simple pattern)
    $text = preg_replace_callback('/\b(?:\+?\d{1,3}[-.\s]?)?(?:\(?\d{3}\)?[-.\s]?)?\d{3}[-.\s]?\d{4}\b/', function($m) use (&$redactions) {
        $redactions[] = ['type'=>'phone','value'=>$m[0]];
        return '[REDACTED-PHONE]';
    }, $text);
    // long numeric tokens (accounts, SSN-like)
    $text = preg_replace_callback('/\b\d{6,}\b/', function($m) use (&$redactions) {
        $redactions[] = ['type'=>'number','value'=>$m[0]];
        return '[REDACTED-NUMBER]';
    }, $text);
    return ['redacted_text' => $text, 'redactions' => $redactions];
}
}
