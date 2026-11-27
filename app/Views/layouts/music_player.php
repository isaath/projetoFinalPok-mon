<!-- Componente reutilizável incluído pelas Views; seus IDs são usados por music_player.js. -->
<div class="music-player" aria-label="Controles da música do jogo">
    <!-- A faixa repete e pode começar a carregar antes de o usuário acionar os controles. -->
    <audio
        id="musica-jogo"
        src="/projetoFinalPok-mon/public/audio/musica_jogar.mp3"
        loop
        preload="auto"
    ></audio>
    <button
        type="button"
        id="botao-musica"
        class="music-toggle"
        aria-pressed="false"
        aria-label="Mutar música"
        title="Mutar música"
    >
        <!-- O CSS troca as ondas pelo símbolo de mudo conforme o estado do botão. -->
        <svg class="music-icon" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 9v6h4l5 4V5L7 9H3z"></path>
            <path class="music-wave" d="M16 9a5 5 0 0 1 0 6"></path>
            <path class="music-wave" d="M18.5 5.5a9 9 0 0 1 0 13"></path>
            <path class="music-mute-mark" d="m16 9 6 6m0-6-6 6"></path>
        </svg>
        <span id="texto-botao-musica" class="music-visually-hidden">Mutar música</span>
    </button>
    <label class="music-volume" for="volume-musica">
        <span class="music-visually-hidden">Volume da música</span>
        <!-- O controle de 0 a 1 é ligado ao volume nativo do elemento audio pelo JavaScript. -->
        <input
            type="range"
            id="volume-musica"
            min="0"
            max="1"
            step="0.05"
            value="0.4"
            aria-label="Volume da música"
        >
    </label>
    <span id="valor-volume-musica" class="music-visually-hidden">40%</span>
    <!-- Região acessível usada para anunciar mudanças e erros da reprodução. -->
    <span id="status-musica" class="music-visually-hidden" role="status" aria-live="polite"></span>
</div>
<!-- defer espera o HTML ser analisado antes de procurar os controles pelos IDs. -->
<script src="/projetoFinalPok-mon/public/js/music_player.js?v=resume-position-2" defer></script>
