window.addEventListener('popstate', function () {
  setTimeout(function () {
    const table = document.getElementById('amami-table');
    if (table) {
      table.scrollIntoView({ behavior: 'smooth' });
    }
  }, 100); // レンダリングが完了するのを待つ
});

// ポップアップを表示する関数
function showPopup(popupId) {
  document.getElementById(popupId).style.display = 'block';
}

// ポップアップを閉じる関数
function closePopup(popupId) {
  document.getElementById(popupId).style.display = 'none';
}


window.onload = function() {
    // Fetch counter data
    fetch('counter.php')  // PHPスクリプトにリクエスト
        .then(response => response.json())  // JSONデータを受け取る
        .then(data => {
            // 取得したカウントデータを表示
            document.getElementById('total-counter').textContent = data.total;
            document.getElementById('japanese-counter').textContent = data.japanese;
            document.getElementById('foreign-counter').textContent = data.foreign;
        })
        .catch(error => console.error('Error fetching data:', error));

    // セルのクリックイベントを設定
    const cells = document.querySelectorAll('td');
    cells.forEach(cell => {
        cell.addEventListener('click', (event) => {
            const url = cell.getAttribute('data-url'); // URLを取得
            if (url && url.trim() !== "") {
                // URLがある場合：ポップアップを出さずにリダイレクト
                sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
                window.location.href = url; // リダイレクト
            } else {
                // URLがない場合：ポップアップを表示
                event.preventDefault(); // デフォルト動作を防止
                sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
                showPopup();
            }
        });
    });
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


// LocalStorageから日本語と外国語のカウントを取得、または初期化
let japaneseCount = parseInt(localStorage.getItem('japaneseCount')) || 0;
let foreignCount = parseInt(localStorage.getItem('foreignCount')) || 0;

// ブラウザの言語を取得
const userLanguage = navigator.language || navigator.userLanguage;

// 言語が日本語の場合
if (userLanguage === 'ja' || userLanguage.startsWith('ja')) {
  japaneseCount++;
  localStorage.setItem('japaneseCount', japaneseCount);
} else {
  // 言語が外国語の場合
  foreignCount++;
  localStorage.setItem('foreignCount', foreignCount);
}

// HTMLにカウントを表示
document.getElementById('japanese-counter').textContent = japaneseCount;
document.getElementById('foreign-counter').textContent = foreignCount;
  // Removed invalid HTML and redundant code
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
// セルのクリックイベントを設定
window.onload = () => {
  const cells = document.querySelectorAll('td');

  cells.forEach(cell => {
    cell.addEventListener('click', (event) => {
      const link = event.target.closest('a'); // クリックされた要素の最も近い祖先の<a>要素を取得
      if (link) {
        const href = link.getAttribute('href'); // href属性の値を取得
        if (href && href !== "" && href !== "#") {
          // href属性がある場合：リダイレクト
          sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
          window.location.href = href; // リダイレクト
        } else {
          // href属性がない場合：ポップアップを表示
          event.preventDefault(); // デフォルト動作を防止
          sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
          showPopup();
        }
      } else {
        // <a>要素がない場合：ポップアップを表示
        event.preventDefault(); // デフォルト動作を防止
        sessionStorage.setItem('scrollPosition', window.scrollY); // スクロール位置を保存
        showPopup();
      }
    });
  });
};

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
 