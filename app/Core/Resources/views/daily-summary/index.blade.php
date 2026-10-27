{{--
    Résumé du jour (DailySummaryController) : l'e-mail tel qu'il sera reçu, pour n'importe quel jour passé,
    sans rien envoyer ; réglages de l'envoi automatique ; e-mail d'essai pour vérifier la messagerie.
--}}
@extends('layouts.app-back-office')

@section('title', __('Daily summary'))
@section('subtitle', __('The summary e-mailed every evening. You can view it here for any day, without sending anything.'))

@php
    $day = $date->toDateString();
    $isToday = $day === $today;
@endphp

@section('content')
    <x-ui.errors />

    <div class="row g-3">
        {{-- Aperçu --}}
        <div class="col-xl-8">
            <x-ui.card :title="__('Preview')" flush>
                <x-slot:toolbar>
                    <form method="GET" action="{{ route('admin.daily-summary.index') }}" class="d-flex flex-wrap align-items-center gap-2">
                        <a href="{{ route('admin.daily-summary.index', ['date' => $date->copy()->subDay()->toDateString()]) }}"
                            class="btn btn-outline-secondary" title="{{ __('Previous day') }}" aria-label="{{ __('Previous day') }}">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                        <input type="date" name="date" value="{{ $day }}" max="{{ $today }}" class="form-control w-auto"
                            aria-label="{{ __('Day') }}" onchange="this.form.submit()">
                        @unless ($isToday)
                            <a href="{{ route('admin.daily-summary.index', ['date' => $date->copy()->addDay()->toDateString()]) }}"
                                class="btn btn-outline-secondary" title="{{ __('Next day') }}" aria-label="{{ __('Next day') }}">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </a>
                            <a href="{{ route('admin.daily-summary.index') }}" class="btn btn-link">{{ __('Today') }}</a>
                        @endunless
                    </form>
                </x-slot:toolbar>

                <iframe src="{{ route('admin.daily-summary.preview', ['date' => $day]) }}" title="{{ __('Preview') }}"
                    class="w-100 border-0 d-block" style="height: 900px; background: #f1f3f5;"></iframe>
            </x-ui.card>
        </div>

        <div class="col-xl-4">
            <div class="ui-stack">
                {{-- Envoi automatique --}}
                <x-ui.card :title="__('Automatic sending')">
                    <form method="POST" action="{{ route('admin.daily-summary.settings') }}">
                        @csrf
                        @method('PUT')

                        <div class="ui-field">
                            {{-- Case décochée : le formulaire n'envoie rien, le champ caché envoie 0 --}}
                            <input type="hidden" name="enabled" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="summary-enabled" name="enabled" value="1"
                                    @checked(old('enabled', $enabled))>
                                <label class="form-check-label" for="summary-enabled">{{ __('Send the summary every day') }}</label>
                            </div>
                        </div>

                        <div class="ui-field">
                            <label class="form-label" for="summary-hour">{{ __('Sending time') }}</label>
                            <select name="hour" id="summary-hour" class="form-select">
                                @foreach (range(0, 23) as $value)
                                    <option value="{{ $value }}" @selected((int) old('hour', $hour) === $value)>{{ sprintf('%02d:00', $value) }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Summary of the current day, sent once. Time zone: :timezone (Settings › Regional & currency).', ['timezone' => $timezone]) }}</div>
                        </div>

                        <div class="ui-field">
                            <label class="form-label" for="summary-recipients">{{ __('Recipients') }}</label>
                            <textarea name="recipients" id="summary-recipients" rows="3" class="form-control"
                                placeholder="gerant@boutique.mg, comptable@boutique.mg">{{ old('recipients', $recipientsSetting) }}</textarea>
                            <div class="form-text">{{ __('Separate the addresses with a comma. Empty: all administrators.') }}</div>
                        </div>

                        <dl class="ui-dl mb-3">
                            <dt>{{ __('Sent to') }}</dt>
                            <dd>{{ $recipients ? implode(', ', $recipients) : __('Nobody: add an address.') }}</dd>
                            <dt>{{ __('Last sent') }}</dt>
                            <dd>{{ $lastSentAt?->format('d/m/Y H:i') ?? __('Never') }}</dd>
                        </dl>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg" aria-hidden="true"></i> {{ __('Save') }}
                        </button>
                    </form>
                </x-ui.card>

                {{-- Essai --}}
                <x-ui.card :title="__('Test the sending')">
                    <p class="text-muted">{{ __('Sends the summary shown on the left to your address only, to check that e-mails go out. An error from the mail server is displayed here.') }}</p>
                    @if ($fakeMailer)
                        <x-ui.alert tone="warning" class="mb-3">
                            {{ __('Mail driver “:mailer”: e-mails are not really sent. Configure an SMTP server (MAIL_ settings) to receive them.', ['mailer' => $mailer]) }}
                        </x-ui.alert>
                    @endif
                    <form method="POST" action="{{ route('admin.daily-summary.test') }}">
                        @csrf
                        <input type="hidden" name="date" value="{{ $day }}">
                        <button type="submit" class="btn btn-outline-primary" @disabled(!auth()->user()->email)>
                            <i class="bi bi-send" aria-hidden="true"></i> {{ __('Send me a test e-mail') }}
                        </button>
                        <div class="form-text">{{ auth()->user()->email }}</div>
                    </form>
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
