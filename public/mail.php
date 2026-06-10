<?php
declare(strict_types=1);

/**
 * お問い合わせフォーム送信スクリプト（エックスサーバー想定）
 *
 * - フォームは name="name" / name="email" / name="message" を送信する
 * - スパム対策のハニーポット（name="website"）が埋まっていたら静かに成功扱いにする
 * - JS(fetch)からのリクエストには JSON、通常のフォーム送信には簡易HTMLを返す
 *
 * 設定：受信先・送信元を自分の環境に合わせて変更してください。
 * ※ $from は「独自ドメインに実在するアドレス」にすること（迷惑メール判定を避けるため）。
 */

// ===== 設定 =====
$to      = 'info@dokkoi.jp';                 // 受信先（問い合わせを受け取るアドレス）
$from    = 'noreply@dokkoi.jp';              // 送信元（dokkoi.jp 上に実在するアドレスにする）
$subject = '【お問い合わせ】ポートフォリオサイト';
$subjectAutoReply = '【dokkoi.jp】お問い合わせありがとうございます';

// ===== 日本語メールの初期設定 =====
mb_language('Japanese');
mb_internal_encoding('UTF-8');

// ===== リクエスト形式の判定（AJAXかどうか）=====
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xrw    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
$isAjax = ($xrw === 'xmlhttprequest') || (strpos($accept, 'application/json') !== false);

/**
 * 結果を返して終了する。
 */
function respond(bool $ok, string $message, bool $isAjax): void
{
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code($ok ? 200 : 400);
    $safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $heading = $ok ? '送信しました' : '送信できませんでした';
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!doctype html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$heading}</title>
<style>
  body { margin:0; min-height:100vh; display:grid; place-items:center;
         background:#0a0e1a; color:#e6edf3;
         font-family:"Hiragino Kaku Gothic ProN","Yu Gothic",sans-serif; }
  .box { text-align:center; padding:2.5rem; max-width:32rem; }
  h1 { font-size:1.4rem; margin:0 0 1rem; }
  p { color:#8b97ab; line-height:1.9; }
  a { display:inline-block; margin-top:1.6rem; padding:.8rem 2rem;
      border:1px solid #00e5ff; color:#00e5ff; border-radius:8px;
      text-decoration:none; font-weight:700; }
</style>
</head>
<body>
  <div class="box">
    <h1>{$heading}</h1>
    <p>{$safe}</p>
    <a href="/contact/">お問い合わせページに戻る</a>
  </div>
</body>
</html>
HTML;
    exit;
}

// ===== POST 以外は弾く =====
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    respond(false, '不正なアクセスです。', $isAjax);
}

// ===== ハニーポット（ボット対策）=====
// 人間には見えない hidden 項目。値が入っていたらボットとみなし、成功を装って終了。
if (trim((string)($_POST['website'] ?? '')) !== '') {
    respond(true, 'お問い合わせを送信しました。ありがとうございます。', $isAjax);
}

// ===== 入力値の取得 =====
$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// ===== バリデーション =====
$errors = [];
if ($name === '') {
    $errors[] = 'お名前';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレス';
}
if ($message === '') {
    $errors[] = 'ご相談内容';
}
if ($errors !== []) {
    respond(false, implode('・', $errors) . ' を正しくご入力ください。', $isAjax);
}

// ===== ヘッダーインジェクション対策 =====
// メールヘッダに使う値に改行が含まれていたら拒否する。
if (preg_match('/[\r\n]/', $name . $email)) {
    respond(false, '不正な入力が検出されました。', $isAjax);
}

// ===== メール本文の組み立て =====
$body = <<<TEXT
ポートフォリオサイトのお問い合わせフォームから送信がありました。

■ お名前
{$name}

■ メールアドレス
{$email}

■ ご相談内容
{$message}

----------------------------------------
送信日時: %s
IPアドレス: %s
TEXT;

$body = sprintf(
    $body,
    date('Y-m-d H:i:s'),
    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
);

// ===== ヘッダー =====
$headers  = 'From: ' . $from . "\r\n";
$headers .= 'Reply-To: ' . $email . "\r\n";
$headers .= 'X-Mailer: PHP/' . phpversion();

// 第5引数で envelope-from を指定（迷惑メール判定対策）
$params = '-f' . $from;

// ===== 送信 =====
// 送信時の警告が出力に混ざって JSON/ヘッダーを壊さないよう抑制し、戻り値で判定する。
$sent = @mb_send_mail($to, $subject, $body, $headers, $params);

if ($sent) {
    // 管理者宛の送信成功後にのみ、送信者へ自動返信を送る。
    $autoReplyBody = <<<TEXT
{$name} 様

このたびはお問い合わせいただきありがとうございます。
以下の内容で受け付けました。

----------------------------------------
■ お名前
{$name}

■ メールアドレス
{$email}

■ ご相談内容
{$message}
----------------------------------------

内容を確認のうえ、担当者よりご連絡いたします。
このメールは送信専用です。ご返信いただいても確認できない場合があります。

--
dokkoi.jp
TEXT;

    $autoReplyHeaders  = 'From: ' . $from . "\r\n";
    $autoReplyHeaders .= 'Reply-To: ' . $from . "\r\n";
    $autoReplyHeaders .= 'X-Mailer: PHP/' . phpversion();

    // 自動返信に失敗しても、問い合わせ自体は受理済みとして成功を返す。
    @mb_send_mail($email, $subjectAutoReply, $autoReplyBody, $autoReplyHeaders, $params);

    respond(true, 'お問い合わせを送信しました。ありがとうございます。', $isAjax);
}

respond(false, '送信処理でエラーが発生しました。お手数ですが時間をおいて再度お試しください。', $isAjax);
