<?php
// Brain Gym family games: stores the shared state of one game room.
// GET  api.php?room=ABCD[&since=N]  -> {"v":N,"state":{...}} or 204 when unchanged since N
// POST api.php  {"room":"ABCD","base":N,"state":{...}}  -> {"v":N+1} or 409 {"v":current,"state":...}
// POST api.php  {"create":true,"state":{...}}  -> {"room":"ABCD","v":1}
// POST api.php  {"feedback":{"tags":[...],"note":"...","where":"...","version":"...","when":"..."}}  -> {"ok":true,"to":"github"|"queued"}
//   Saves the note to rooms/feedback-pending.jsonl, then commits every pending note to ROADMAP.md on GitHub.
//   Needs a GitHub token in braingym-secret.php two folders up (outside public_html, never in this public repo):
//   <?php return ['github_token' => 'github_pat_...'];
// POST api.php  {"report":{"key":"<32 hex>","data":{...}}}  -> {"ok":true}   (the iPad's latest progress, for the family report link)
// GET  api.php?report=<32 hex>  -> {"t":unix,"data":{...}} or 404
// GET  api.php?reminder=1&d=YYYYMMDD&t=HH:MM[&t2=HH:MM]  -> a calendar file (.ics) with a daily Brain Gym reminder
//   (two events when t2 is given). The iPad offers to add it to Calendar.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_BYTES = 40000;
const MAX_REPORT_BYTES = 300000;
const MAX_AGE = 86400; // rooms are deleted after a day
const LETTERS = 'ABCDEFGHJKMNPQRSTUVWXYZ';

function fail($code, $msg) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }

function room_dir() {
  $d = __DIR__ . '/rooms';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  if (is_dir($d) && is_writable($d)) return $d;
  $t = sys_get_temp_dir() . '/braingym-rooms';
  if (!is_dir($t)) @mkdir($t, 0700, true);
  if (is_dir($t) && is_writable($t)) return $t;
  fail(500, 'storage unavailable');
}

function room_path($room) {
  if (!is_string($room) || !preg_match('/^[A-Z]{4}$/', $room)) fail(400, 'bad room');
  return room_dir() . '/' . $room . '.json';
}

function read_room($path) {
  $raw = @file_get_contents($path);
  if ($raw === false) return null;
  $j = json_decode($raw, true);
  return is_array($j) ? $j : null;
}

function cleanup() {
  foreach (glob(room_dir() . '/*.json') ?: [] as $f) {
    if (@filemtime($f) < time() - MAX_AGE) @unlink($f);
  }
}

/* ---------- feedback from the app ---------- */
const FEEDBACK_TAGS = ['Too hard', 'Too easy', 'Confusing', 'Hard to see', 'Hard to hear', 'Something broke', 'I liked this', 'I have an idea'];
const GITHUB_REPO = 'MediaPotion/Brain-Gym';
const ROADMAP = 'ROADMAP.md';
const FEEDBACK_MARK = '<!-- feedback:start -->';

// Plain text only: no markdown, links, HTML or @mentions in the public roadmap.
function clean($s, $max) {
  if (!is_string($s)) return '';
  $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s);
  $s = str_replace(['@', '://'], [' at ', ' '], $s);
  $s = preg_replace('/[<>`*_\[\]#|\\\\~]/u', '', $s);
  $s = trim(preg_replace('/\s+/u', ' ', $s));
  return mb_substr($s, 0, $max);
}

function feedback_line($f) {
  $parts = [$f['when'] ?: date('Y-m-d'), $f['version'], $f['where']];
  $line = '- ' . implode(' · ', array_filter($parts, 'strlen'));
  if ($f['tags']) $line .= ' · **' . implode(', ', $f['tags']) . '**';
  if ($f['note'] !== '') $line .= ' · “' . $f['note'] . '”';
  return $line;
}

function rate_ok($dir) {
  $path = $dir . '/feedback-times.log';
  $now = time();
  $times = array_filter(array_map('intval', @file($path, FILE_IGNORE_NEW_LINES) ?: []), fn($x) => $x > $now - 86400);
  $hour = count(array_filter($times, fn($x) => $x > $now - 3600));
  if (count($times) >= 30 || $hour >= 10) return false;
  $times[] = $now;
  @file_put_contents($path, implode("\n", $times), LOCK_EX);
  return true;
}

function github_token() {
  $env = getenv('BRAINGYM_GITHUB_TOKEN');
  if ($env) return $env;
  $f = dirname(__DIR__, 2) . '/braingym-secret.php';
  if (!is_file($f)) return null;
  $cfg = @include $f;
  return is_array($cfg) && !empty($cfg['github_token']) ? $cfg['github_token'] : null;
}

function github($method, $path, $token, $body = null) {
  $ch = curl_init('https://api.github.com/repos/' . GITHUB_REPO . $path);
  $headers = ['Authorization: Bearer ' . $token, 'Accept: application/vnd.github+json', 'User-Agent: braingym-feedback', 'X-GitHub-Api-Version: 2022-11-28'];
  if ($body !== null) { $headers[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); }
  curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15]);
  $res = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $res === false ? null : json_decode($res, true)];
}

// Adds the lines under the feedback marker in ROADMAP.md (newest first). Returns true when committed.
function push_to_roadmap($lines) {
  $token = github_token();
  if (!$token || !function_exists('curl_init')) return false;
  for ($try = 0; $try < 2; $try++) {
    [$code, $file] = github('GET', '/contents/' . ROADMAP . '?ref=main', $token);
    if ($code !== 200 || empty($file['sha'])) return false;
    $text = base64_decode(str_replace("\n", '', $file['content']));
    $block = implode("\n", array_reverse($lines));
    if (strpos($text, FEEDBACK_MARK) !== false) $text = str_replace(FEEDBACK_MARK, FEEDBACK_MARK . "\n" . $block, $text);
    else $text = rtrim($text) . "\n\n## Feedback from the app\nSent with the Feedback button, newest first. Turn these into roadmap items above, then delete them here.\n" . FEEDBACK_MARK . "\n" . $block . "\n";
    $n = count($lines);
    [$code] = github('PUT', '/contents/' . ROADMAP, $token, [
      'message' => 'Feedback from the app' . ($n > 1 ? " ($n notes)" : ''),
      'content' => base64_encode($text), 'sha' => $file['sha'], 'branch' => 'main']);
    if ($code === 200 || $code === 201) return true;
    if ($code !== 409) return false;
  }
  return false;
}

function handle_feedback($in) {
  $f = $in['feedback'];
  if (!is_array($f)) fail(400, 'bad request');
  $tags = array_values(array_intersect(FEEDBACK_TAGS, is_array($f['tags'] ?? null) ? $f['tags'] : []));
  $fb = ['tags' => $tags, 'note' => clean($f['note'] ?? '', 1000), 'where' => clean($f['where'] ?? '', 80),
    'version' => clean($f['version'] ?? '', 20), 'when' => clean($f['when'] ?? '', 40)];
  if (!$fb['tags'] && $fb['note'] === '') fail(400, 'empty');
  $dir = room_dir();
  if (!rate_ok($dir)) fail(429, 'too many');
  $pending = $dir . '/feedback-pending.jsonl';
  $fh = @fopen($pending, 'c+');
  if ($fh === false) fail(500, 'storage unavailable');
  flock($fh, LOCK_EX);
  fseek($fh, 0, SEEK_END);
  fwrite($fh, json_encode($fb) . "\n");
  rewind($fh);
  $lines = [];
  while (($row = fgets($fh)) !== false) { $j = json_decode($row, true); if (is_array($j)) $lines[] = feedback_line($j); }
  $sent = push_to_roadmap($lines);
  if ($sent) { ftruncate($fh, 0); @file_put_contents($dir . '/feedback-sent.jsonl', json_encode($fb) . "\n", FILE_APPEND | LOCK_EX); }
  flock($fh, LOCK_UN); fclose($fh);
  echo json_encode(['ok' => true, 'to' => $sent ? 'github' : 'queued']);
  exit;
}

/* ---------- daily reminder (calendar file) ---------- */
function reminder() {
  $d = preg_match('/^\d{8}$/', $_GET['d'] ?? '') ? $_GET['d'] : date('Ymd');
  $times = [];
  foreach (['t', 't2'] as $k) {
    if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $_GET[$k] ?? '', $m)) continue;
    $times[] = sprintf('%02d%02d', $m[1], $m[2]);
  }
  if (!$times) $times = ['1000'];
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
  $host = preg_replace('/[^A-Za-z0-9.:-]/', '', $_SERVER['HTTP_HOST'] ?? 'mediapotion.net');
  $url = ($https ? 'https' : 'http') . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/braingym/api.php'), '/') . '/';
  $n = count($times);
  $out = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Brain Gym//Reminder//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH'];
  foreach ($times as $i => $hm) {
    $title = $n > 1 ? 'Brain Gym, part ' . ($i + 1) . ' of 2' : 'Brain Gym time';
    $out = array_merge($out, ['BEGIN:VEVENT',
      'UID:braingym-' . bin2hex(random_bytes(8)) . '@' . preg_replace('/:\d+$/', '', $host),
      'DTSTAMP:' . gmdate('Ymd\THis\Z'),
      'DTSTART:' . $d . 'T' . $hm . '00',
      'DURATION:PT' . ($n > 1 ? 10 : 20) . 'M',
      'RRULE:FREQ=DAILY',
      'SUMMARY:' . $title,
      'DESCRIPTION:Open Brain Gym and do today\'s plan.',
      'URL:' . $url,
      'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . $title, 'TRIGGER:PT0M', 'END:VALARM',
      'END:VEVENT']);
  }
  $out[] = 'END:VCALENDAR';
  header('Content-Type: text/calendar; charset=utf-8');
  header('Content-Disposition: inline; filename="brain-gym-reminder.ics"');
  echo implode("\r\n", $out) . "\r\n";
  exit;
}

/* ---------- family report (latest progress from the iPad) ---------- */
function report_path($key) {
  if (!is_string($key) || !preg_match('/^[a-f0-9]{32}$/', $key)) fail(400, 'bad key');
  $d = room_dir() . '/reports';
  if (!is_dir($d)) @mkdir($d, 0755, true);
  return $d . '/' . $key . '.json';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'GET' && isset($_GET['reminder'])) reminder();
if ($method === 'GET' && isset($_GET['report'])) {
  $raw = @file_get_contents(report_path($_GET['report']));
  if ($raw === false) fail(404, 'no report yet');
  echo $raw;
  exit;
}

if ($method === 'GET') {
  $path = room_path($_GET['room'] ?? '');
  $r = read_room($path);
  if (!$r) fail(404, 'no such room');
  $since = isset($_GET['since']) ? (int)$_GET['since'] : -1;
  if ($since === (int)$r['v']) { http_response_code(204); exit; }
  echo json_encode($r);
  exit;
}

if ($method !== 'POST') fail(405, 'method not allowed');

$raw = file_get_contents('php://input', false, null, 0, MAX_REPORT_BYTES + 1);
if ($raw === false || strlen($raw) > MAX_REPORT_BYTES) fail(413, 'too large');
$in = json_decode($raw, true);
if (is_array($in) && isset($in['report'])) {
  $r = $in['report'];
  if (!is_array($r) || !isset($r['data']) || !is_array($r['data'])) fail(400, 'bad request');
  $path = report_path($r['key'] ?? '');
  if (@file_put_contents($path, json_encode(['t' => time(), 'data' => $r['data']]), LOCK_EX) === false) fail(500, 'storage unavailable');
  echo json_encode(['ok' => true]);
  exit;
}
if (strlen($raw) > MAX_BYTES) fail(413, 'too large');
if (is_array($in) && isset($in['feedback'])) handle_feedback($in);
if (!is_array($in) || !isset($in['state']) || !is_array($in['state'])) fail(400, 'bad request');

if (!empty($in['create'])) {
  cleanup();
  for ($i = 0; $i < 40; $i++) {
    $room = '';
    for ($k = 0; $k < 4; $k++) $room .= LETTERS[random_int(0, strlen(LETTERS) - 1)];
    $path = room_path($room);
    $fh = @fopen($path, 'x');
    if ($fh === false) continue;
    fwrite($fh, json_encode(['v' => 1, 'state' => $in['state']]));
    fclose($fh);
    echo json_encode(['room' => $room, 'v' => 1]);
    exit;
  }
  fail(503, 'no free room');
}

$path = room_path($in['room'] ?? '');
$fh = @fopen($path, 'c+');
if ($fh === false) fail(404, 'no such room');
flock($fh, LOCK_EX);
$cur = json_decode(stream_get_contents($fh), true);
if (!is_array($cur)) { flock($fh, LOCK_UN); fclose($fh); @unlink($path); fail(404, 'no such room'); }
if ((int)($in['base'] ?? -1) !== (int)$cur['v']) {
  flock($fh, LOCK_UN); fclose($fh);
  http_response_code(409);
  echo json_encode($cur);
  exit;
}
$next = ['v' => (int)$cur['v'] + 1, 'state' => $in['state']];
ftruncate($fh, 0); rewind($fh);
fwrite($fh, json_encode($next));
fflush($fh);
flock($fh, LOCK_UN); fclose($fh);
echo json_encode(['v' => $next['v']]);
