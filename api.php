<?php
// Brain Gym family games: stores the shared state of one game room.
// GET  api.php?room=ABCD[&since=N]  -> {"v":N,"state":{...}} or 204 when unchanged since N
// POST api.php  {"room":"ABCD","base":N,"state":{...}}  -> {"v":N+1} or 409 {"v":current,"state":...}
// POST api.php  {"create":true,"state":{...}}  -> {"room":"ABCD","v":1}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MAX_BYTES = 40000;
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

$raw = file_get_contents('php://input', false, null, 0, MAX_BYTES + 1);
if ($raw === false || strlen($raw) > MAX_BYTES) fail(413, 'too large');
$in = json_decode($raw, true);
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
