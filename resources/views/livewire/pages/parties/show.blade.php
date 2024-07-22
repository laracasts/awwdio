<?php

use Livewire\Volt\Component;
use App\Models\ListeningParty;
use Livewire\Attributes\Lazy;

new #[Lazy] class extends Component {
    public ListeningParty $listeningParty;

    public $isFinished = false;

    public function mount(ListeningParty $listeningParty)
    {
        if ($this->listeningParty->end_time && $this->listeningParty->end_time->isPast()) {
            $this->isFinished = true;
        }
        $this->listeningParty = $listeningParty->load('episode.podcast');
    }

    public function placeholder()
    {
        return <<<'HTML'
        <div class="flex items-center justify-center min-h-screen bg-emerald-50">

                <div class="flex items-center justify-center space-x-8">
                    <div class="relative flex items-center justify-center w-16 h-16">
                        <span
                            class="absolute inline-flex rounded-full opacity-75 size-10 bg-emerald-400 animate-ping"></span>
                        <span
                            class="relative inline-flex items-center justify-center text-2xl font-bold text-white rounded-full size-12 bg-emerald-500">
                            🫶
                            </svg>
                        </span>
                    </div>

            </div>
        </div>
        HTML;
    }
}; ?>

<div x-data="{
    audio: null,
    isLoading: true,
    isLive: false,
    isPlaying: false,
    countdownText: '',
    isReady: false,
    currentTime: 0,
    startTimestamp: {{ $listeningParty->start_time->timestamp }},
    endTimestamp: {{ $listeningParty->end_time ? $listeningParty->end_time->timestamp : 'null' }},
    copyNotification: false,

    init() {
        this.startCountdown();
        if (this.$refs.audioPlayer && !this.isFinished) {
            this.initializeAudioPlayer();
        }
    },

    initializeAudioPlayer() {
        this.audio = this.$refs.audioPlayer;
        this.audio.addEventListener('loadedmetadata', () => {
            this.isLoading = false;
            this.checkLiveStatus();
        });

        this.audio.addEventListener('timeupdate', () => {
            this.currentTime = this.audio.currentTime;
            if (this.endTimestamp && this.currentTime >= (this.endTimestamp - this.startTimestamp)) {
                this.finishListeningParty();
            }
        });

        this.audio.addEventListener('play', () => {
            this.isPlaying = true;
            this.isReady = true;
        });

        this.audio.addEventListener('pause', () => {
            this.isPlaying = false;
        });

        this.audio.addEventListener('ended', () => {
            this.finishListeningParty();
        });
    },

    finishListeningParty() {
        $wire.isFinished = true;
        $wire.$refresh();
        this.isPlaying = false;
        if (this.audio) {
            this.audio.pause();
        }
    },

    startCountdown() {
        this.checkLiveStatus();
        setInterval(() => this.checkLiveStatus(), 1000);
    },

    checkLiveStatus() {
        const now = Math.floor(Date.now() / 1000);
        const timeUntilStart = this.startTimestamp - now;

        if (timeUntilStart <= 0) {
            this.isLive = true;
            this.countdownText = 'Live';
            if (this.audio && !this.isPlaying && !this.isFinished) {
                this.playAudio();
            }
        } else {
            const days = Math.floor(timeUntilStart / 86400);
            const hours = Math.floor((timeUntilStart % 86400) / 3600);
            const minutes = Math.floor((timeUntilStart % 3600) / 60);
            const seconds = timeUntilStart % 60;
            this.countdownText = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }
    },

    playAudio() {
        if (!this.audio) return;
        const now = Math.floor(Date.now() / 1000);
        const elapsedTime = Math.max(0, now - this.startTimestamp);
        this.audio.currentTime = elapsedTime;
        this.audio.play().catch(error => {
            console.error('Playback failed:', error);
            this.isPlaying = false;
            this.isReady = false;
        });
    },

    joinAndBeReady() {
        this.isReady = true;
        if (this.isLive && this.audio && !this.isFinished) {
            this.playAudio();
        }
    },

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    },

    copyToClipboard() {
        navigator.clipboard.writeText(window.location.href);
        this.copyNotification = true;
        setTimeout(() => {
            this.copyNotification = false;
        }, 3000);
    }

}" x-init="init()">
    @if ($listeningParty->end_time === null)
        <div class="flex items-center justify-center min-h-screen bg-emerald-50" wire:poll.5s>
            <div class="w-full max-w-2xl p-8 mx-8 bg-white rounded-lg shadow-lg">
                <div class="flex items-center justify-center space-x-8">
                    <div class="relative flex items-center justify-center w-16 h-16">
                        <span
                            class="absolute inline-flex rounded-full opacity-75 size-10 bg-emerald-400 animate-ping"></span>
                        <span
                            class="relative inline-flex items-center justify-center text-2xl font-bold text-white rounded-full size-12 bg-emerald-500">
                            🫶
                            </svg>
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-serif text-lg font-semibold text-slate-900">Creating your listening party</p>
                        <p class="mt-1 text-sm text-slate-600">
                            The awwd.io room <span class="font-bold"> {{ $listeningParty->name }}</span> is being put
                            together...
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @elseif($isFinished)
        <div class="flex items-center justify-center min-h-screen bg-emerald-50">
            <div class="w-full max-w-2xl p-8 mx-8 text-center bg-white rounded-lg shadow-lg">
                <h2 class="mb-4 text-2xl font-bold text-slate-900">This listening party has finished</h2>
                <p class="text-slate-600">Thank you for joining the {{ $listeningParty->name }} listening party.</p>
                <p class="mt-2 text-slate-600">The podcast "{{ $listeningParty->episode->title }}" is no longer live.
                </p>
            </div>
        </div>
    @else
        <audio x-ref="audioPlayer" :src="'{{ $listeningParty->episode->media_url }}'" preload="auto"></audio>

        <div x-show="!isLive" class="flex items-center justify-center min-h-screen bg-emerald-50" x-cloak>
            <div class="relative w-full max-w-2xl p-6 bg-white rounded-lg shadow-lg">
                <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                        <x-avatar src="{{ $listeningParty->episode->podcast->artwork_url }}" size="xl"
                            rounded="sm" alt="Podcast Artwork" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[0.9rem] font-semibold truncate text-slate-900">
                            {{ $listeningParty->name }}</p>
                        <div class="mt-0.8">
                            <p class="max-w-xs text-sm truncate text-slate-600">
                                {{ $listeningParty->episode->title }}</p>
                            <p class="text-[0.7rem] tracking-tighter uppercase text-slate-400">
                                {{ $listeningParty->episode->podcast->title }}</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="font-serif font-semibold tracking-tight text-slate-600">Starting in:</p>
                    <p class="font-mono text-3xl font-semibold tracking-wider text-emerald-700" x-text="countdownText">
                    </p>
                </div>

                <div class="mt-6">
                    <x-button x-show="!isReady" class="w-full" @click="joinAndBeReady()">Join and Be Ready</x-button>
                </div>

                <h2 x-show="isReady"
                    class="mt-8 font-serif text-lg tracking-tight text-center text-slate-900 font-bolder">
                    Ready to start the awwd.io party! Stay tuned. 🫶</h2>

                <div class="flex items-center justify-end mt-8">
                    <button @click="copyToClipboard();"
                        class="flex items-center justify-center w-auto h-8 px-3 py-1 text-xs bg-white border rounded-md cursor-pointer border-neutral-200/60 hover:bg-neutral-100 active:bg-white focus:bg-white focus:outline-none text-neutral-500 hover:text-neutral-600 group">
                        <span x-show="!copyNotification">Share Listening Party URL</span>
                        <svg x-show="!copyNotification" class="w-4 h-4 ml-1.5 stroke-current"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <span x-show="copyNotification" class="tracking-tight text-emerald-500" x-cloak>Copied!</span>
                        <svg x-show="copyNotification" class="w-4 h-4 ml-1.5 text-green-500 stroke-current"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3l1.5 1.5 3-3.75" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>


        <div x-show="isLive" x-cloak>
            <div>{{ $listeningParty->podcast->title }}</div>
            <div>{{ $listeningParty->episode->title }}</div>
            <div>Current Time: <span x-text="formatTime(currentTime)"></span></div>
            <div>Start Time: {{ $listeningParty->start_time }}</div>
            <div x-show="isLoading">Loading...</div>

            <x-button x-show="!isReady" class="w-full mt-8" @click="joinAndBeReady()">Join and Be Ready</x-button>
        </div>
    @endif
</div>
