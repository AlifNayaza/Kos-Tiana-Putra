@props(['compact' => false])
@php
    // Potongan HP hanya menampilkan A-03, A-04, dan kios; versi desktop menampilkan seluruh gedung.
    $viewBox = $compact ? '250 196 510 204' : '0 0 760 400';

    // lit = lampu menyala sejak awal; flick = lampu nyala-mati bergantian (penghuni datang & pergi).
    $doors = [
        ['no' => 'PEMILIK', 'tw' => 62, 'x' => 40,  'y' => 92,  'role' => 'pengelola', 'lit' => false, 'flick' => false],
        ['no' => 'B-01',    'tw' => 44, 'x' => 150, 'y' => 92,  'role' => null,        'lit' => false, 'flick' => 0],
        ['no' => 'B-02',    'tw' => 44, 'x' => 260, 'y' => 92,  'role' => null,        'lit' => true,  'flick' => false],
        ['no' => 'B-03',    'tw' => 44, 'x' => 370, 'y' => 92,  'role' => null,        'lit' => false, 'flick' => 7],
        ['no' => 'A-01',    'tw' => 44, 'x' => 40,  'y' => 272, 'role' => null,        'lit' => false, 'flick' => 3.5],
        ['no' => 'A-02',    'tw' => 44, 'x' => 150, 'y' => 272, 'role' => null,        'lit' => true,  'flick' => false],
        ['no' => 'A-03',    'tw' => 44, 'x' => 260, 'y' => 272, 'role' => null,        'lit' => false, 'flick' => 10],
        ['no' => 'A-04',    'tw' => 44, 'x' => 370, 'y' => 272, 'role' => 'penghuni',  'lit' => true,  'flick' => false],
    ];
    $mono = 'ui-monospace, SFMono-Regular, Menlo, monospace';
@endphp

@once
<style>
  /* Semua animasi hanya mengubah transform/opacity, berhenti saat ilustrasi tidak terlihat,
     dan dimatikan untuk pengguna yang memilih "kurangi gerakan". */
  .kos-scene .kos-node { transition: color .25s ease; }
  .kos-scene .kos-node.is-on { color: #fff; }
  .kos-scene .halo { opacity: 0; transition: opacity .4s ease; }
  .kos-scene .pane { fill: var(--color-ochre); opacity: 0; transition: opacity .4s ease; }
  .kos-scene .lit .halo { opacity: 1; animation: kos-fade .5s ease-out backwards; animation-delay: var(--d, 0s); }
  .kos-scene .lit .pane { opacity: .6; animation: kos-fade .5s ease-out backwards; animation-delay: var(--d, 0s); }
  .kos-scene .flick .halo { animation: kos-flick-halo 14s ease-in-out infinite; animation-delay: var(--d, 0s); }
  .kos-scene .flick .pane { animation: kos-flick-pane 14s ease-in-out infinite; animation-delay: var(--d, 0s); }
  .kos-scene .kos-node.is-on .halo { opacity: 1; animation: none; }
  .kos-scene .kos-node.is-on .pane { opacity: .9; animation: none; }
  .kos-scene .tag { transition: fill .25s ease, stroke .25s ease; }
  .kos-scene .kos-node.is-on .tag { fill: var(--color-ochre); stroke: var(--color-ochre); }
  .kos-scene .kos-node.is-on .tag-text { fill: var(--color-ink); }

  .kos-scene .steam { stroke-dasharray: 4 6; animation: kos-steam 5s linear infinite; }
  .kos-scene .bulb { fill: var(--color-ochre); animation: kos-bulb 1.4s steps(1) infinite; }
  .kos-scene .bulb.b { animation-delay: -.7s; }
  .kos-scene .kos-node.is-on .bulb { animation: none; opacity: 1; }
  .kos-scene .buka { transform-box: view-box; transform-origin: 512px 282px; animation: kos-swing 3.6s ease-in-out infinite; }
  .kos-scene .cloth { transform-box: fill-box; transform-origin: 50% 0; animation: kos-sway 3.2s ease-in-out infinite alternate; }
  .kos-scene .leaf { transform-box: fill-box; transform-origin: 50% 100%; animation: kos-sway 4s ease-in-out infinite alternate; }
  .kos-scene .tail { transform-box: view-box; transform-origin: 724px 210px; animation: kos-tail 2.4s ease-in-out infinite; }
  .kos-scene .bubble { animation: kos-bubble 2.6s ease-in infinite; }
  .kos-scene .trolley { animation: kos-trolley 11s cubic-bezier(.45,0,.35,1) infinite; }
  .kos-scene .wheel { transform-box: fill-box; transform-origin: center; animation: kos-wheel 11s cubic-bezier(.45,0,.35,1) infinite; }
  .kos-scene .cargo { animation: kos-cargo 11s linear infinite; }
  .kos-scene .thanks { transform-box: view-box; transform-origin: 404px 306px; animation: kos-thanks 11s ease-out infinite; }
  .kos-scene .doorglow { animation: kos-doorglow 11s ease-in-out infinite; }

  .kos-scene.kos-paused * { animation-play-state: paused !important; }

  @keyframes kos-fade { from { opacity: 0; } }
  @keyframes kos-flick-halo { 0%, 30% { opacity: 0; } 34%, 70% { opacity: 1; } 74%, 100% { opacity: 0; } }
  @keyframes kos-flick-pane { 0%, 30% { opacity: 0; } 34%, 70% { opacity: .6; } 74%, 100% { opacity: 0; } }
  @keyframes kos-steam { to { stroke-dashoffset: -40; } }
  @keyframes kos-bulb { 0% { opacity: 1; } 50% { opacity: .25; } }
  @keyframes kos-swing { 0%, 100% { transform: rotate(-7deg); } 50% { transform: rotate(7deg); } }
  @keyframes kos-sway { from { transform: rotate(-5deg); } to { transform: rotate(5deg); } }
  @keyframes kos-tail { 0%, 100% { transform: rotate(0); } 50% { transform: rotate(-22deg); } }
  @keyframes kos-bubble { 0% { transform: translateY(0); opacity: 0; } 20% { opacity: .9; } 100% { transform: translateY(-9px); opacity: 0; } }
  /* Galon diantar dari kios ke A-04, penghuni bilang makasih, troli kembali kosong lalu diisi lagi. */
  @keyframes kos-trolley { 0%, 8% { transform: translateX(0); } 38%, 54% { transform: translateX(-94px); } 84%, 100% { transform: translateX(0); } }
  @keyframes kos-wheel { 0%, 8% { transform: rotate(0); } 38%, 54% { transform: rotate(-540deg); } 84%, 100% { transform: rotate(0); } }
  @keyframes kos-cargo { 0%, 42% { opacity: 1; transform: translateY(0); } 48% { opacity: 0; transform: translateY(-10px); } 90% { opacity: 0; transform: translateY(-10px); } 96%, 100% { opacity: 1; transform: translateY(0); } }
  @keyframes kos-thanks { 0%, 42% { opacity: 0; transform: scale(.5); } 47% { opacity: 1; transform: scale(1.08); } 50%, 60% { opacity: 1; transform: scale(1); } 66%, 100% { opacity: 0; transform: scale(.9); } }
  @keyframes kos-doorglow { 0%, 40% { opacity: 0; } 47%, 58% { opacity: 1; } 68%, 100% { opacity: 0; } }

  /* Mode "kurangi gerakan": tidak ada yang bergeser/berayun, tapi suasana tetap hidup lewat
     cahaya (lampu papan, jendela, uap, gelembung) yang hanya berubah opacity. */
  @media (prefers-reduced-motion: reduce) {
    .kos-scene .cloth, .kos-scene .leaf, .kos-scene .tail, .kos-scene .buka,
    .kos-scene .trolley, .kos-scene .wheel, .kos-scene .cargo { animation: none !important; }
    .kos-scene .bubble { animation-name: kos-bubble-fade; }
    .kos-scene .thanks { animation-name: kos-thanks-fade; }
    /* app.css memaksa semua animasi 0.01ms di mode ini; animasi cahaya yang aman dipulihkan di sini. */
    .kos-scene .bulb { animation-duration: 2.4s !important; animation-iteration-count: infinite !important; }
    .kos-scene .flick .halo, .kos-scene .flick .pane { animation-duration: 14s !important; animation-iteration-count: infinite !important; }
    .kos-scene .steam { animation-duration: 5s !important; animation-iteration-count: infinite !important; }
    .kos-scene .bubble { animation-duration: 2.6s !important; animation-iteration-count: infinite !important; }
    .kos-scene .thanks, .kos-scene .doorglow { animation-duration: 11s !important; animation-iteration-count: infinite !important; }
    @keyframes kos-bubble-fade { 0%, 100% { opacity: 0; } 50% { opacity: .9; } }
    @keyframes kos-thanks-fade { 0%, 42% { opacity: 0; } 48%, 60% { opacity: 1; } 66%, 100% { opacity: 0; } }
  }
</style>
@endonce

<div {{ $attributes->merge(['class' => 'kos-scene w-full']) }}
     x-data="{ r: null }"
     x-on:kos-role.window="r = $event.detail"
     x-init="if ('IntersectionObserver' in window) new IntersectionObserver(([e]) => $el.classList.toggle('kos-paused', !e.isIntersecting)).observe($el)"
     aria-hidden="true">

  <svg viewBox="{{ $viewBox }}" class="block h-auto w-full text-white/60" fill="none"
       stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
       focusable="false" preserveAspectRatio="xMidYMax meet">

    {{-- Gedung: atap + toren air, pelat lantai 2, tanah --}}
    <path d="M10 40H750M26 40V54H734V40"/>
    <rect x="78" y="10" width="44" height="22" rx="11"/>
    <path d="M90 10V6h20v4M86 32v8M114 32v8"/>
    <path d="M10 212H750M26 220H734"/>
    <path d="M486 220V380"/>
    <rect x="0" y="380" width="760" height="20" fill="var(--color-brand-deep)" stroke="none"/>
    <path d="M0 380H760"/>

    {{-- Teras jemur lantai 2: baju bergoyang, tanaman, kucing kos --}}
    <path d="M504 96Q620 124 736 96"/>
    <path class="cloth" style="animation-delay:-.4s" d="M546 104l-10 8 6 10 6-3v30h22v-30l6 3 6-10-10-8"/>
    <rect class="cloth" style="animation-delay:-1.6s" x="612" y="110" width="28" height="40" rx="2"/>
    <path class="cloth" style="animation-delay:-2.4s" d="M672 106v48h12l2-26 2 26h12v-48Z"/>
    <path d="M516 212l3-12h16l3 12Z"/>
    <path class="leaf" d="M527 200c-7-8-3-17 0-21M527 200c4-8 10-11 13-12M527 200c-6-4-12-5-15-2"/>
    <g transform="translate(700 184)">
      <path d="M0 28C-2 14 6 8 14 8C22 8 26 16 24 28Z" fill="var(--color-brand)"/>
      <circle cx="14" cy="4" r="6" fill="var(--color-brand)"/>
      <path d="M9 0L9-6L13-2M19 0L19-6L15-2"/>
    </g>
    <path class="tail" d="M724 210C734 210 736 198 730 196"/>

    {{-- Pintu & jendela kamar --}}
    @foreach ($doors as $i => $d)
      @php $x = $d['x']; $y = $d['y']; $tw = $d['tw']; $role = $d['role']; @endphp
      <g class="kos-node" @if($role) x-bind:class="{ 'is-on': r === '{{ $role }}' }" @endif>
        <g class="{{ $d['lit'] ? 'lit' : '' }} {{ $d['flick'] !== false ? 'flick' : '' }}"
           style="--d: {{ $d['flick'] !== false ? '-' . $d['flick'] : number_format($i * 0.15, 2) }}s">
          <circle class="halo" cx="{{ $x + 74 }}" cy="{{ $y + 46 }}" r="66" fill="var(--color-ochre)" fill-opacity=".08" stroke="none"/>
          <circle class="halo" cx="{{ $x + 74 }}" cy="{{ $y + 46 }}" r="40" fill="var(--color-ochre)" fill-opacity=".12" stroke="none"/>
          <rect class="pane" x="{{ $x + 56 }}" y="{{ $y + 20 }}" width="36" height="52" stroke="none"/>
          <rect x="{{ $x + 56 }}" y="{{ $y + 20 }}" width="36" height="52"/>
          <path d="M{{ $x + 74 }} {{ $y + 20 }}v52M{{ $x + 56 }} {{ $y + 46 }}h36"/>
        </g>
        <rect x="{{ $x }}" y="{{ $y }}" width="48" height="108" rx="2"/>
        <rect x="{{ $x + 8 }}" y="{{ $y + 10 }}" width="32" height="40" rx="1" opacity=".45"/>
        <circle cx="{{ $x + 38 }}" cy="{{ $y + 62 }}" r="2.2" fill="currentColor" stroke="none"/>
        <rect class="tag" x="{{ $x + 24 - $tw / 2 }}" y="{{ $y - 26 }}" width="{{ $tw }}" height="18" rx="3" fill="none" stroke-width="1"/>
        <text class="tag-text" x="{{ $x + 24 }}" y="{{ $y - 13 }}" text-anchor="middle"
              font-family="{{ $mono }}" font-size="12" font-weight="600" fill="currentColor" stroke="none">{{ $d['no'] }}</text>
      </g>
    @endforeach

    {{-- Pintu A-04 menyala saat galon sampai --}}
    <circle class="doorglow" cx="394" cy="326" r="58" fill="var(--color-ochre)" fill-opacity=".16" stroke="none"/>

    {{-- Kios Tiana: bagian dari kos dengan pintu masuk sendiri (makan, minum, galon) --}}
    <g class="kos-node" x-bind:class="{ 'is-on': r === 'petugas' }">
      <g class="lit" style="--d: .6s">
        <circle class="halo" cx="620" cy="318" r="128" fill="var(--color-ochre)" fill-opacity=".07" stroke="none"/>
        <circle class="halo" cx="620" cy="318" r="74" fill="var(--color-ochre)" fill-opacity=".12" stroke="none"/>
      </g>

      {{-- papan nama dengan lampu berkedip --}}
      <path d="M562 220v6M678 220v6"/>
      <rect class="tag" x="548" y="226" width="144" height="26" rx="4" fill="var(--color-brand-deep)"/>
      <text class="tag-text" x="620" y="244" text-anchor="middle" font-family="{{ $mono }}" font-size="13" font-weight="700"
            letter-spacing="2" fill="currentColor" stroke="none">KIOS TIANA</text>
      @for ($b = 0; $b < 9; $b++)
        <circle class="bulb {{ $b % 2 ? 'b' : '' }}" cx="{{ 556 + $b * 16 }}" cy="226" r="2.2" stroke="none"/>
        <circle class="bulb {{ $b % 2 ? '' : 'b' }}" cx="{{ 556 + $b * 16 }}" cy="252" r="2.2" stroke="none"/>
      @endfor

      {{-- kanopi bergaris --}}
      @for ($s = 0; $s < 10; $s++)
        @php $sx = 500 + $s * 24; $fill = $s % 2 ? 'fill="currentColor" fill-opacity=".14"' : 'fill="var(--color-ochre)" fill-opacity=".85"'; @endphp
        <path d="M{{ $sx }} 260h24v14a12 8 0 0 1-24 0Z" {!! $fill !!} stroke="none"/>
      @endfor
      <path d="M496 260H744"/>
      <path d="M508 282V380M732 282V380"/>

      {{-- rak: galon dengan gelembung air, camilan, toples --}}
      <path d="M528 314H716"/>
      @foreach ([536, 562, 588] as $gi => $gx)
        <rect x="{{ $gx + 6 }}" y="{{ 289 }}" width="8" height="5" rx="1"/>
        <rect x="{{ $gx }}" y="294" width="20" height="20" rx="4"/>
        <rect x="{{ $gx + 2 }}" y="301" width="16" height="11" rx="2.5" fill="#7cc4dd" fill-opacity=".4" stroke="none"/>
        <circle class="bubble" style="animation-delay: -{{ $gi * .8 }}s" cx="{{ $gx + 7 }}" cy="310" r="1.3" fill="#fff" stroke="none"/>
        <circle class="bubble" style="animation-delay: -{{ $gi * .8 + 1.3 }}s" cx="{{ $gx + 13 }}" cy="310" r="1" fill="#fff" stroke="none"/>
      @endforeach
      @foreach ([622, 644] as $k => $sx)
        <path d="M{{ $sx }} 314V298l3-3 3 3 3-3 3 3 3-3 3 3V314Z" @if($k === 0) fill="var(--color-ochre)" fill-opacity=".3" @endif/>
      @endforeach
      <rect x="672" y="298" width="18" height="16" rx="3"/>
      <path d="M670 294h22"/>
      <rect x="698" y="300" width="12" height="14" rx="2"/>

      {{-- konter: mangkuk mi beruap dan gelas --}}
      <rect x="556" y="336" width="164" height="44" rx="2"/>
      <text x="638" y="363" text-anchor="middle" font-family="{{ $mono }}" font-size="9.5" font-weight="600"
            letter-spacing="1" fill="currentColor" stroke="none" opacity=".85">MAKAN · MINUM · GALON</text>
      <path d="M570 336a16 11 0 0 0 32 0"/>
      <path d="M590 322l14-9M594 325l14-7"/>
      <path class="steam" d="M579 330c-4-5 4-8 0-13M587 330c-4-5 4-8 0-13"/>
      <rect x="682" y="320" width="14" height="16" rx="3"/>
      <path d="M696 324h3a3 3 0 0 1 0 6h-3"/>

      {{-- papan BUKA yang berayun --}}
      <g class="buka">
        <path d="M512 282l-8 14M512 282l8 14"/>
        <rect x="496" y="296" width="32" height="15" rx="3" fill="var(--color-surface)" stroke="none"/>
        <text x="512" y="307" text-anchor="middle" font-family="{{ $mono }}" font-size="9" font-weight="700"
              fill="var(--color-brand-deep)" stroke="none">BUKA</text>
      </g>
    </g>

    {{-- Troli galon: kios ke A-04, lalu kembali --}}
    <g transform="translate(518 370)">
      <g class="trolley">
        <g class="cargo">
          <rect x="8" y="-34" width="10" height="6" rx="1" fill="var(--color-brand)"/>
          <rect x="3" y="-28" width="20" height="26" rx="5" fill="var(--color-brand)"/>
          <rect x="5" y="-19" width="16" height="15" rx="3" fill="#7cc4dd" fill-opacity=".45" stroke="none"/>
        </g>
        <path d="M0 0H30M30 0L36-30M33-30h6"/>
        <g class="wheel"><circle cx="6" cy="5" r="5" fill="var(--color-brand-deep)"/><path d="M6 1v8M2 5h8"/></g>
        <g class="wheel"><circle cx="24" cy="5" r="5" fill="var(--color-brand-deep)"/><path d="M24 1v8M20 5h8"/></g>
      </g>
    </g>

    {{-- Balon ucapan penghuni A-04 --}}
    <g class="thanks">
      <rect x="374" y="286" width="62" height="20" rx="10" fill="var(--color-surface)" stroke="none"/>
      <path d="M392 305l-4 8 10-8Z" fill="var(--color-surface)" stroke="none"/>
      <text x="405" y="300" text-anchor="middle" font-size="10" font-weight="700" fill="var(--color-brand-deep)" stroke="none">Makasih!</text>
    </g>
  </svg>
</div>
