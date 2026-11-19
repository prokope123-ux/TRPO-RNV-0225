#!/usr/bin/env bash
# Сквозная проверка: поднимает встроенный сервер на копии dataset и выполняет цепочки запросов.
# Запуск: bash tests/smoke.sh   (нужны php и curl)
set -u
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TMP="$(mktemp -d)"
PORT="${PORT:-8089}"
BASE="http://127.0.0.1:$PORT"
cp -r "$ROOT/data" "$TMP/data"
APP_DATA_DIR="$TMP/data" php -S "127.0.0.1:$PORT" -t "$ROOT/public" >"$TMP/server.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null; rm -rf "$TMP"' EXIT
sleep 1

FAILED=0
check() { # check "описание" "результат команды" "ожидаемая подстрока"
  if [[ "$2" == *"$3"* ]]; then echo "[OK  ] $1"; else echo "[FAIL] $1 (ожидалось «$3»)"; FAILED=$((FAILED+1)); fi
}
token() { curl -s -b "$1" -c "$1" "$BASE/" | grep -o 'name="csrf" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//'; }
post()  { local jar=$1; shift; local t; t=$(token "$jar"); curl -s -b "$jar" -c "$jar" -o /dev/null -d "csrf=$t&return=notes&$*" "$BASE/index.php"; }
page()  { curl -s -b "$1" -c "$1" "$BASE/${2:-}"; }

TAB="$TMP/tab.txt"; PRIVATE="$TMP/private.txt"

check "главная отдаёт список заметок из notes.json" "$(page $TAB)" "Конспект: жизненный цикл HTTP-запроса"
check "неизвестный page → 404" "$(curl -s -o /dev/null -w '%{http_code}' "$BASE/?page=nope")" "404"
check "первый визит: Гость" "$(page $TAB)" '>Гость<'

post $TAB "action=login&user_id=2"
check "обычная вкладка: после входа имя Анна Смирнова" "$(page $TAB)" "Анна Смирнова"
check "обычная вкладка: имя сохраняется между запросами" "$(page $TAB '?page=users')" "Анна Смирнова"
check "приватное окно (другая cookie): всё ещё Гость" "$(page $PRIVATE)" '>Гость<'

post $TAB "action=clear_name"
check "очистка имени возвращает Гость" "$(page $TAB)" '>Гость<'

post $TAB "action=login&user_id=99"
check "несуществующий пользователь → сообщение" "$(page $TAB)" "Пользователь не найден"
post $TAB "action=set_theme&theme=neon"
check "недопустимая тема → сообщение" "$(page $TAB)" "Недопустимый режим"
post $TAB "action=toggle_favorite&note_id=3"
check "избранное: 1 заметка" "$(page $TAB)" "избранных в этой сессии: 1"

# Добавление заметки
before=$(grep -c '"id"' "$TMP/data/notes.json")
post $TAB "action=add_note&title=&body="
check "пустая форма не пишет в файл" "$(grep -c '"id"' "$TMP/data/notes.json")" "$before"
t=$(token $TAB)
curl -s -b $TAB -c $TAB -o /dev/null --data-urlencode "action=add_note" --data-urlencode "csrf=$t" \
     --data-urlencode "title=Проверка smoke" --data-urlencode "body=Текст из автотеста" --data-urlencode "tags=a, b" "$BASE/index.php"
check "заметка записана в notes.json" "$(cat "$TMP/data/notes.json")" "Проверка smoke"
check "после redirect заметка видна на странице" "$(page $TAB)" "Проверка smoke"
curl -s -b $TAB -c $TAB -o /dev/null -d "action=add_note&title=csrf&body=csrf csrf" "$BASE/index.php"
check "POST без CSRF-токена отклонён" "$(page $TAB)" "Форма устарела"

check "сценарии session: 8 из 8" "$(page $TAB '?page=scenarios')" "Пройдено 8 из 8"

# Повреждённый файл
echo '[{"id": 1,' > "$TMP/data/notes.json"
out=$(page $TAB)
check "повреждённый notes.json → понятное сообщение" "$out" "повреждён"
check "повреждённый notes.json → нет Fatal error" "$([[ "$out" == *"Fatal error"* ]] && echo bad || echo good)" "good"
rm "$TMP/data/notes.json"
check "нет notes.json → понятное сообщение" "$(page $TAB)" "не найден"

check "в логе сервера нет Warning/Notice/Fatal" "$([[ $(grep -c -E 'Warning|Notice|Fatal|Deprecated' "$TMP/server.log") -eq 0 ]] && echo good || echo bad)" "good"

echo; [[ $FAILED -eq 0 ]] && echo "Все проверки пройдены" || echo "Провалено: $FAILED"
exit $FAILED
