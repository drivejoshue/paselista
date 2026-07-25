<style>
    .sp-ai-layout {
        height: calc(100vh - 126px);
        min-height: 640px;
    }

    .sp-ai-history {
        width: 292px;
        min-width: 292px;
    }

    .sp-ai-scroll {
        min-height: 0;
        overflow-y: auto;
        scroll-behavior: smooth;
    }

    .sp-ai-user-bubble {
        max-width: min(720px, 88%);
        white-space: pre-wrap;
    }

    .sp-ai-assistant-content {
        width: min(900px, 100%);
        min-width: 0;
    }

    .sp-ai-answer {
        line-height: 1.7;
        white-space: pre-line;
    }

    .sp-ai-composer textarea {
        min-height: 42px;
        max-height: 160px;
        resize: none;
    }

    .sp-ai-question-button {
        min-height: 54px;
        white-space: normal;
        line-height: 1.3;
    }

    .sp-ai-chart-host {
        min-height: 290px;
        width: 100%;
    }

    .sp-ai-chart-svg {
        width: 100%;
        min-height: 270px;
        display: block;
    }

    .sp-ai-chart-legend {
        font-size: .75rem;
    }

    .sp-ai-conversation-title {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sp-ai-thinking-dot {
        animation: sp-ai-pulse 1.15s infinite;
    }

    .sp-ai-thinking-dot:nth-child(2) {
        animation-delay: .18s;
    }

    .sp-ai-thinking-dot:nth-child(3) {
        animation-delay: .36s;
    }

    @keyframes sp-ai-pulse {
        0%, 60%, 100% { opacity: .3; }
        30% { opacity: 1; }
    }

    @media (max-width: 991.98px) {
        .sp-ai-layout {
            height: calc(100vh - 112px);
            min-height: 560px;
        }
    }

    @media (max-width: 575.98px) {
        .sp-ai-layout {
            height: calc(100vh - 102px);
        }

        .sp-ai-user-bubble {
            max-width: 94%;
        }
    }
</style>
