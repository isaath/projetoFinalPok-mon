// Mantém os detalhes da música isolados para não criar variáveis globais na página.
(function () {
    // Recupera o áudio e os controles definidos no componente music_player.php.
    const audio = document.getElementById('musica-jogo');
    const toggleButton = document.getElementById('botao-musica');
    const toggleText = document.getElementById('texto-botao-musica');
    const volumeSlider = document.getElementById('volume-musica');
    const volumeLabel = document.getElementById('valor-volume-musica');
    const status = document.getElementById('status-musica');

    // Algumas páginas podem não incluir o player; nesse caso não há nada a configurar.
    if (!audio || !toggleButton || !toggleText || !volumeSlider || !volumeLabel || !status) {
        return;
    }

    // Preferências duráveis: sobrevivem ao fechamento e reabertura do navegador.
    const volumeKey = 'pokebagual.music.volume';
    const mutedKey = 'pokebagual.music.muted';
    // Posição temporária: fica limitada à aba atual durante a navegação entre telas.
    const timeKey = 'pokebagual.music.time';
    let localStorageAvailable = true;
    let sessionStorageAvailable = true;
    let savedTime = 0;
    let lastSavedSecond = -1;
    let volume = 0.4;
    let muted = false;

    // Lê e valida volume/mudo salvos; valores fora do intervalo são ignorados.
    try {
        const savedVolumeText = window.localStorage.getItem(volumeKey);
        const savedVolume = savedVolumeText === null ? null : Number(savedVolumeText);
        if (
            savedVolume !== null
            && Number.isFinite(savedVolume)
            && savedVolume >= 0
            && savedVolume <= 1
        ) {
            volume = savedVolume;
        }
        muted = window.localStorage.getItem(mutedKey) === 'true';
    } catch (error) {
        // Armazenamento pode estar bloqueado pelo navegador ou pelas configurações de privacidade.
        localStorageAvailable = false;
        console.error('Não foi possível ler as preferências de música.', error);
        status.textContent = 'As preferências de volume não puderam ser carregadas.';
    }

    // Restaura o ponto da faixa salvo na sessão atual, se houver.
    try {
        const timeText = window.sessionStorage.getItem(timeKey);
        const time = timeText === null ? null : Number(timeText);
        if (time !== null && Number.isFinite(time) && time > 0) {
            savedTime = time;
        }
    } catch (error) {
        sessionStorageAvailable = false;
        console.error('Não foi possível carregar a posição da música.', error);
        status.textContent = 'A posição da música não será retomada nesta sessão.';
    }

    // Aplica o estado inicial aos controles antes de tentar reproduzir.
    audio.volume = volume;
    audio.muted = muted;
    volumeSlider.value = String(volume);

    // Atualiza textos acessíveis, rótulos e aparência do botão com o estado do áudio.
    function atualizarControles() {
        const volumePercentual = Math.round(audio.volume * 100);
        volumeLabel.textContent = volumePercentual + '%';

        const textoBotao = audio.paused
            ? 'Ativar música'
            : (audio.muted ? 'Ativar som' : 'Mutar música');
        toggleText.textContent = textoBotao;
        toggleButton.setAttribute('aria-label', textoBotao);
        toggleButton.title = textoBotao;
        toggleButton.setAttribute('aria-pressed', audio.muted ? 'true' : 'false');
        toggleButton.classList.toggle('is-muted', audio.muted || audio.volume === 0);
    }

    // Persiste volume e mudo para serem reutilizados em visitas futuras.
    function guardarPreferencias() {
        if (!localStorageAvailable) {
            return;
        }

        try {
            window.localStorage.setItem(volumeKey, String(audio.volume));
            window.localStorage.setItem(mutedKey, String(audio.muted));
        } catch (error) {
            localStorageAvailable = false;
            console.error('Não foi possível salvar as preferências de música.', error);
            status.textContent = 'As alterações de volume não puderam ser salvas.';
        }
    }

    // Persiste a posição da faixa na aba atual para retomá-la em outra página.
    function guardarPosicao() {
        if (!sessionStorageAvailable || !Number.isFinite(audio.currentTime)) {
            return;
        }

        try {
            window.sessionStorage.setItem(timeKey, String(audio.currentTime));
        } catch (error) {
            sessionStorageAvailable = false;
            console.error('Não foi possível salvar a posição da música.', error);
            status.textContent = 'A posição da música não será retomada nesta sessão.';
        }
    }

    // Tenta iniciar o áudio e apresenta uma instrução se o navegador bloquear autoplay.
    function iniciarMusica() {
        audio.play().then(function () {
            status.textContent = audio.muted ? 'Música mutada.' : 'Música tocando.';
            atualizarControles();
        }).catch(function (error) {
            console.warn('A reprodução automática da música foi bloqueada.', error);
            status.textContent = error.name === 'NotAllowedError'
                ? 'Clique em “Ativar música” para começar.'
                : 'Não foi possível carregar a música.';
            atualizarControles();
        });
    }

    // Aguarda os metadados para garantir que a posição salva esteja dentro da faixa.
    function retomarPosicaoEIniciar() {
        if (
            savedTime > 0
            && Number.isFinite(audio.duration)
            && savedTime < audio.duration
        ) {
            audio.currentTime = savedTime;
        }
        iniciarMusica();
    }

    // O botão inicia a faixa pausada ou alterna o estado de mudo enquanto ela toca.
    toggleButton.addEventListener('click', function () {
        if (audio.paused) {
            audio.muted = false;
            guardarPreferencias();
            iniciarMusica();
            return;
        }

        audio.muted = !audio.muted;
        status.textContent = audio.muted ? 'Música mutada.' : 'Música tocando.';
        guardarPreferencias();
        atualizarControles();
    });

    // O slider ajusta volume; zero também ativa o estado visual/declarado de mudo.
    volumeSlider.addEventListener('input', function () {
        audio.volume = Number(volumeSlider.value);
        if (audio.volume === 0) {
            audio.muted = true;
        } else if (audio.muted) {
            audio.muted = false;
        }

        guardarPreferencias();
        atualizarControles();
        if (audio.paused && audio.volume > 0) {
            iniciarMusica();
        }
    });

    // Só é possível validar currentTime contra a duração depois que os metadados carregam.
    audio.addEventListener('loadedmetadata', retomarPosicaoEIniciar);
    audio.addEventListener('timeupdate', function () {
        const segundoAtual = Math.floor(audio.currentTime);
        // Salva no máximo uma vez por segundo para evitar gravações desnecessárias.
        if (segundoAtual !== lastSavedSecond) {
            lastSavedSecond = segundoAtual;
            guardarPosicao();
        }
    });
    // Erros do arquivo de áudio são visíveis ao usuário por uma mensagem acessível.
    audio.addEventListener('error', function () {
        console.error('O arquivo de música não pôde ser carregado.', audio.error);
        status.textContent = 'Não foi possível carregar a música.';
    });
    // Tenta preservar o ponto atual quando a aba fica oculta ou a página é fechada.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            guardarPosicao();
        }
    });
    window.addEventListener('pagehide', guardarPosicao);
    window.addEventListener('beforeunload', guardarPosicao);

    // Sincroniza o estado visual e inicia/retoma assim que o áudio estiver pronto.
    atualizarControles();
    if (audio.readyState >= 1) {
        retomarPosicaoEIniciar();
    }
})();
