// 右側表示状態なら右端までスクロールする関数
function scrollTableRightIfNeeded() {
  if (!isRightSide) return;
  var table = document.getElementById('amami-table');
  if (table) {
    var wrap = table.parentElement;
    if (wrap && wrap.scrollWidth > wrap.clientWidth) {
      wrap.scrollLeft = wrap.scrollWidth - wrap.clientWidth;
    }
  }
  // --- 横スクロール位置の復元（sessionStorage優先） ---
  try {
    var savedScroll = sessionStorage.getItem('amami-table-scrollLeft');
    if (savedScroll !== null && wrap) {
      wrap.scrollLeft = parseInt(savedScroll, 10);
    }
  } catch (e) {}
}

// ページロード・履歴復元時にも右端へ
window.addEventListener('DOMContentLoaded', scrollTableRightIfNeeded);
window.addEventListener('load', scrollTableRightIfNeeded);
window.addEventListener('pageshow', scrollTableRightIfNeeded);
// 右側表示状態をグローバルで保持（localStorageで永続化）
var isRightSide = false;
try {
  if (localStorage.getItem('amamiTableIsRightSide') === '1') {
    isRightSide = true;
  }
} catch(e) {}
window.addEventListener('popstate', function () {
  setTimeout(function () {
    const table = document.getElementById('amami-table');
    if (table) {
      table.scrollIntoView({ behavior: 'smooth' });
    }
    // 右側表示状態なら右端までスクロール
    if (isRightSide) {
      var wrap = table.parentElement;
      if (wrap && wrap.scrollWidth > wrap.clientWidth) {
        wrap.scrollLeft = wrap.scrollWidth - wrap.clientWidth;
      }
      // --- 横スクロール位置の復元（sessionStorage優先） ---
      try {
        var savedScroll = sessionStorage.getItem('amami-table-scrollLeft');
        if (savedScroll !== null && wrap) {
          wrap.scrollLeft = parseInt(savedScroll, 10);
        }
      } catch (e) {}
    }
  }, 100); // レンダリングが完了するのを待つ
});

function tableCellHasContent(cell) {
  const text = cell.textContent
    .replace(/\u00a0/g, ' ')
    .replace(/\u200b/g, '')
    .trim();
  if (text.length > 0) return true;
  return !!cell.querySelector('img, iframe, video, canvas, svg, picture');
}

// ポップアップを表示する関数
function showPopup(popupId) {
  document.getElementById(popupId).style.display = 'block';
}

// ポップアップを閉じる関数
function closePopup(popupId) {
  document.getElementById(popupId).style.display = 'none';
}


window.onload = function () {
    // セルのクリックイベントを設定 (変更前)
  const cells = document.querySelectorAll('td');
  cells.forEach(cell => {
    cell.addEventListener('click', (event) => {
      if (!cell.hasAttribute('data-url')) return;
      if (!tableCellHasContent(cell)) return;
      const url = cell.getAttribute('data-url'); // URLを取得
      if (url && url.trim() !== "") {
        // URLがある場合：ポップアップを出さずにリダイレクト
        sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
          // 横スクロール位置も保存
          try {
            var table = document.getElementById('amami-table');
            if (table && table.parentElement) {
              sessionStorage.setItem('amami-table-scrollLeft', table.parentElement.scrollLeft);
            }
          } catch (e) {}
        window.location.href = url; // リダイレクト
      } else {
        // data-url 指定ありで空の場合のみポップアップ
        event.preventDefault(); // デフォルト動作を防止
        sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
          // 横スクロール位置も保存
          try {
            var table = document.getElementById('amami-table');
            if (table && table.parentElement) {
              sessionStorage.setItem('amami-table-scrollLeft', table.parentElement.scrollLeft);
            }
          } catch (e) {}
        showPopup();
      }
    });
  });
  
    var btnPanLeft  = document.getElementById('amami-table-pan-left');
    var btnPanRight = document.getElementById('amami-table-pan-right');
    if (btnPanLeft) {
      btnPanLeft.addEventListener('click', function () {
        smoothScrollTo(0);
        isRightSide = false;
        try { localStorage.setItem('amamiTableIsRightSide', '0'); } catch(e) {}
      });
    }
    if (btnPanRight) {
      btnPanRight.addEventListener('click', function () {
        var max = Math.max(0, wrap.scrollWidth - wrap.clientWidth);
        smoothScrollTo(max);
        isRightSide = true;
        try { localStorage.setItem('amamiTableIsRightSide', '1'); } catch(e) {}
      });
    }
  
    // セルクリックや履歴戻りでも右側表示は維持（ボタンで解除するまで）
    // 既存のセルクリックやリンククリックの処理には影響しない
};

// ポップアップを表示
function showPopup() {
  document.getElementById('overlay').style.display = 'block';
  document.getElementById('popup').style.display = 'block';
}

// ポップアップを閉じてスクロール位置を復元する関数
function closePopup() {
  document.getElementById('overlay').style.display = 'none';
  document.getElementById('popup').style.display = 'none';

  const savedScrollPosition = sessionStorage.getItem('scrollPosition');
  if (savedScrollPosition !== null) {
    window.scrollTo(0, parseInt(savedScrollPosition, 10)); // スクロール位置を復元
  }
}



// メール送信ボタンのクリックイベント



// スライドショー
let slides = document.querySelectorAll("#slideshow img");
let currentSlide = 0;

function showNextSlide() {
  slides[currentSlide].classList.remove("active");
  currentSlide = (currentSlide + 1) % slides.length; // 最後まで行ったら最初に戻る
  slides[currentSlide].classList.add("active");
}

// 初期スライドを表示
slides[currentSlide].classList.add("active");

// 4秒ごとにスライドを切り替え
setInterval(showNextSlide, 3000);


// Removed invalid HTML and redundant code

// 旧カウントロジック完全削除済み
const playlist = [
  "hp-music/furusato_kitahara.mp3",
  "hp-music/shima_blues.mp3",
  "hp-music/yoronbojo.mp3",
  "hp-music/kakeromabojo.mp3",
  "hp-music/shima_sodachi.mp3",
  "hp-music/tokunoshima_kouta.mp3",
  "hp-music/funaki_koukou_sannensei.mp3",
  "hp-music/sinkawa_furusato.mp3",
  "hp-music/shima_blues_misawa.mp3",
  "hp-music/tokyo_amami_kai_kaika.mp3"
];
let pausedTime = 0; // 一時停止した時間を保持する変数

function playTrack(trackIndex) {
  audioPlayer.src = playlist[trackIndex];
  audioPlayer.load();
  audioPlayer.currentTime = pausedTime; // 一時停止した時間から再生
  audioPlayer.play();
  isPlaying = true;
  playButton.textContent = "一時停止";
}

playButton.addEventListener('click', () => {
  if (isPlaying) {
    audioPlayer.pause();
    isPlaying = false;
    pausedTime = audioPlayer.currentTime; // 一時停止した時間を保持
    playButton.textContent = "再生";
  } else {
    playTrack(currentTrack);
  }
});

stopButton.addEventListener('click', () => {
  audioPlayer.pause();
  audioPlayer.currentTime = 0;
  pausedTime = 0; // 一時停止時間をリセット
  isPlaying = false;
  playButton.textContent = "再生";
});

audioPlayer.addEventListener('ended', () => {
  currentTrack = (currentTrack + 1) % playlist.length;
  playTrack(currentTrack);
});



let currentTrack = 0;
const audioPlayer = document.getElementById('audioPlayer');
const playButton = document.getElementById('playButton');
const stopButton = document.getElementById('stopButton');
let isPlaying = false; // 再生状態を管理する変数
audioPlayer.currentTime = pausedTime; // 一時停止した時間から再生
audioPlayer.play();
isPlaying = true;
playButton.textContent = "一時停止";
}

playButton.addEventListener('click', () => {
  if (isPlaying) {
    audioPlayer.pause();
    isPlaying = false;
    pausedTime = audioPlayer.currentTime; // 一時停止した時間を保持
    playButton.textContent = "再生";
  } else {
    playTrack(currentTrack);
  }
});

stopButton.addEventListener('click', () => {
  audioPlayer.pause();
  audioPlayer.currentTime = 0;
  pausedTime = 0; // 一時停止時間をリセット
  isPlaying = false;
  playButton.textContent = "再生";
});

audioPlayer.addEventListener('ended', () => {
  currentTrack = (currentTrack + 1) % playlist.length;
  playTrack(currentTrack);
});
// 観光テーブル：貼付（準備中）は「空の href / # のリンク」クリック時のみ
window.onload = () => {
  const table = document.getElementById('amami-table');
  if (!table) return;

  table.addEventListener('click', (event) => {
    const cell = event.target.closest('td');
    if (!cell || !table.contains(cell)) return;
    if (event.target.closest('audio, video, input, button, select, textarea')) return;

    const link = event.target.closest('a');
    if (!link || !cell.contains(link)) return;

    const hrefRaw = link.getAttribute('href');
    const h = (hrefRaw || '').trim();
    if (/^\s*javascript:/i.test(h)) return;

    const isPlaceholder = hrefRaw === null || h === '' || h === '#';
    if (isPlaceholder) {
      if (link.getAttribute('onclick')) return;
      event.preventDefault();
      sessionStorage.setItem('scrollPosition', window.scrollY);
      // 横スクロール位置も保存
      try {
        if (table.parentElement) {
          sessionStorage.setItem('amami-table-scrollLeft', table.parentElement.scrollLeft);
        }
      } catch (e) {}
      showPopup();
      return;
    }

    event.preventDefault();
    sessionStorage.setItem('scrollPosition', window.scrollY);
    // 横スクロール位置も保存
    try {
      if (table.parentElement) {
        sessionStorage.setItem('amami-table-scrollLeft', table.parentElement.scrollLeft);
      }
    } catch (e) {}
    window.location.href = hrefRaw;
  });
};
// ページ離脱時にも横スクロール位置を保存
window.addEventListener('beforeunload', function () {
  try {
    var table = document.getElementById('amami-table');
    if (table && table.parentElement) {
      sessionStorage.setItem('amami-table-scrollLeft', table.parentElement.scrollLeft);
    }
  } catch (e) {}
});

//　VOICEの改善

let voices = [];

function speak(text) {
  const synth = window.speechSynthesis;
  voices = synth.getVoices();

  const utterance = new SpeechSynthesisUtterance(text);
  utterance.lang = 'ja-JP';
  utterance.pitch = 1.1;
  utterance.rate = 1.0;

  // Google日本語が使えるなら指定（あれば一番自然）
  const voice = voices.find(v => v.name.includes('Google 日本語'));
  if (voice) utterance.voice = voice;

  synth.cancel(); // 他の発話をキャンセル
  synth.speak(utterance);
}

// Safari対策：初回で voices が取得できないことがあるので空発話で初期化
window.speechSynthesis.onvoiceschanged = () => {
  voices = window.speechSynthesis.getVoices();
};

/* ...既存のCSSがある場合はその下に追加... */

console.log('hello');

// 遷移元のページに戻る関数
function goBackToSource() {
  const referrer = document.referrer;
  
  if (referrer) {
    // document.referrer がある場合は、その前ページに遷移
    window.location.href = referrer;
  } else {
    // referrer がない場合は履歴で戻る
    history.back();
  }
}
