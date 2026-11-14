#!/usr/bin/env bash
# Syntax checks for Brain Gym. Run from anywhere.
set -e
cd "$(dirname "$0")/.."
node -e "const t=require('fs').readFileSync('index.html','utf8');const m=t.match(/<script>([\s\S]*)<\/script>/);new Function(m[1]);console.log('index.html script: ok')"
php -l api.php
python3 -c "import json;json.load(open('photos/list.json'));print('photos/list.json: ok')"
