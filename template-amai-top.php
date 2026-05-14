<?php
/**
 * Template Name: 東京奄美会トップ（奄美観光導線・旧ファイル名 amai）
 *
 * 新規は template-amami-top.php を使ってください。内容は同一です。
 * 言語切替は amami.html と同一の Google 翻訳ウィジェットです。
 */
if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

<!-- amami.html と同一: Google 翻訳（言語切替） -->
<div style="display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 8px; padding: 10px; background: #f0f0f0; margin-bottom: 1em;">
	<div id="google_translate_element" class="box" style="margin-right: 16px;"></div>
	<div id="title" style="font-size:20px; font-weight:bold;">言語切替</div>
</div>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<script type="text/javascript">
  function googleTranslateElementInit() {
    console.log("googleTranslateElementInit 呼び出し");
    new google.translate.TranslateElement({
      pageLanguage: 'ja',
      includedLanguages: 'ja,en,zh-CN,ko,fr,es,zh-TW,de,it,pt,ru',
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE
    }, 'google_translate_element');
  }

  function retryGoogleTranslateInit() {
    let attempts = 0;
    let maxAttempts = 10;
    let intervalId = setInterval(() => {
      const elem = document.getElementById('google_translate_element');
      if (elem && elem.innerHTML.trim()) {
        clearInterval(intervalId);
        console.log("翻訳バーが正常に表示されました。");
      } else {
        console.log("翻訳バー再初期化", attempts);
        googleTranslateElementInit();
        if (++attempts >= maxAttempts) {
          clearInterval(intervalId);
        }
      }
    }, 1000);
  }

  window.addEventListener('load', function () {
    retryGoogleTranslateInit();
  });
</script>

<main id="primary" class="site-main">
	<?php
	while (have_posts()) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<?php the_title('<h1 class="entry-title">', '</h1>'); ?>
			</header>
			<div class="entry-content">
				<?php the_content(); ?>

				<div class="amami-travel-info-links" style="margin-top:1.25em;padding:1em 1.1em;border:1px solid #9fc5f5;border-radius:12px;background:linear-gradient(135deg,#f7fbff 0%,#e9f4ff 55%,#dff0ff 100%);max-width:42rem;">
					<p style="margin:0 0 0.75em;line-height:1.75;color:#123d78;">奄美群島12市町村の観光・公式リンクなどを一覧した案内ページです。</p>
					<p style="margin:0;line-height:1.75;">
						<a href="https://violetfoal2.sakura.ne.jp/hp-amami-pr-1/amami.html" target="_blank" rel="noopener noreferrer" style="font-weight:700;color:#1b5eb8;">奄美群島12市町村への旅行情報を見る</a>
					</p>
				</div>
			</div>
		</article>
		<?php
	}
	?>
</main>

<?php
get_footer();
