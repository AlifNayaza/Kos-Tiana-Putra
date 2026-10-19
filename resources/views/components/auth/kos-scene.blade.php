@props(['compact' => false])
@php
    $viewBox = $compact ? '260 236 500 164' : '0 0 760 400';
    $doors = [
        ['no' => 'PEMILIK', 'tw' => 62, 'x' => 40,  'y' => 92,  'role' => 'pengelola', 'lit' => false],
        ['no' => 'B-01',    'tw' => 44, 'x' => 150, 'y' => 92,  'role' => null,        'lit' => false],
        ['no' => 'B-02',    'tw' => 44, 'x' => 260, 'y' => 92,  'role' => null,        'lit' => true],
        ['no' => 'B-03',    'tw' => 44, 'x' => 370, 'y' => 92,  'role' => null,        'lit' => false],
        ['no' => 'A-01',    'tw' => 44, 'x' => 40,  'y' => 272, 'role' => null,        'lit' => false],
        ['no' => 'A-02',    'tw' => 44, 'x' => 150, 'y' => 272, 'role' => null,        'lit' => true],
        ['no' => 'A-03',    'tw' => 44, 'x' => 260, 'y' => 272, 'role' => null,        'lit' => false],
        ['no' => 'A-04',    'tw' => 44, 'x' => 370, 'y' => 272, 'role' => 'penghuni',  'lit' => true],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}
     x-data="{ r: null }"
     x-on:kos-role.window="r = $event.detail"
     aria-hidden="true">
  <style>
    .kos-node { transition: color .25s ease; }
    .kos-node.is-on { color: white; }
    .kos-win .halo { opacity: 0; transition: opacity .4s ease; }
    .kos-win .pane { fill: var(--color-ochre); opacity: 0; transition: opacity .4s ease; }
    .kos-win.lit .halo { opacity: 1; animation: kos-fade .35s ease-out backwards; animation-delay: var(--d, 0s); }
    .kos-win.lit .pane { opacity: .6; animation: kos-fade .35s ease-out backwards; animation-delay: var(--d, 0s); }
    .kos-node.is-on .kos-win .halo { opacity: 1; }
    .kos-node.is-on .kos-win .pane { opacity: .9; }
    .kos-node .tag { transition: fill .25s ease, stroke .25s ease; }
    .kos-node.is-on .tag { fill: var(--color-ochre); stroke: var(--color-ochre); }
    .kos-node.is-on .tag-text { fill: var(--color-ink); }
    .kos-steam { stroke-dasharray: 4 6; animation: kos-steam 6s linear infinite; }
    @keyframes kos-fade { from { opacity: 0; } }
    @keyframes kos-steam { to { stroke-dashoffset: -40; } }
    @media (prefers-reduced-motion: reduce) {
      .kos-win .halo, .kos-win .pane, .kos-steam { animation: none !important; transition: none !important; }
    }
  </style>

  <svg viewBox="{{ $viewBox }}" class="block h-auto w-full text-white/60" fill="none"
       stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
       focusable="false" preserveAspectRatio="xMidYMax meet">

    {{-- bangunan: atap, lantai 2, tanah --}}
    <path d="M10 40H750M26 40V54H734V40"/>
    <path d="M10 212H750M26 220H734"/>
    <rect x="0" y="380" width="760" height="20" fill="var(--color-brand-deep)" stroke="none"/>
    <path d="M0 380H760"/>

    {{-- jemuran di lantai 2 kanan --}}
    <path d="M504 96Q620 124 736 96"/>
    <path d="M546 104l-10 8 6 10 6-3v30h22v-30l6 3 6-10-10-8"/>
    <rect x="612" y="110" width="28" height="40" rx="2"/>
    <path d="M672 106v48h12l2-26 2 26h12v-48Z"/>

    {{-- pintu dan jendela (digenerate dari array $doors) --}}
    @foreach ($doors as $i => $d)
      @php
        $x = $d['x']; $y = $d['y']; $tw = $d['tw'];
        $role = $d['role'];
      @endphp
      <g class="kos-node" @if($role) x-bind:class="{ 'is-on': r === '{{ $role }}' }" @endif>
        <g class="kos-win {{ $d['lit'] ? 'lit' : '' }}" style="--d: {{ number_format($i * 0.15, 2) }}s">
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
              font-family="ui-monospace, SFMono-Regular, Menlo, monospace" font-size="12" font-weight="600"
              fill="currentColor" stroke="none">{{ $d['no'] }}</text>
      </g>
    @endforeach

    {{-- warkop --}}
    <g class="kos-node" x-bind:class="{ 'is-on': r === 'petugas' }">
      <g class="kos-win lit" style="--d: .7s">
        <circle class="halo" cx="628" cy="325" r="125" fill="var(--color-ochre)" fill-opacity=".07" stroke="none"/>
        <circle class="halo" cx="628" cy="325" r="72" fill="var(--color-ochre)" fill-opacity=".12" stroke="none"/>
      </g>
      <path d="M500 272L516 246H724L740 272Z"/>
      <path d="M516 272V380M724 272V380"/>
      <rect class="tag" x="566" y="280" width="124" height="22" rx="3" fill="none" stroke-width="1"/>
      <text class="tag-text" x="628" y="296" text-anchor="middle"
            font-family="ui-monospace, SFMono-Regular, Menlo, monospace" font-size="13" font-weight="600"
            fill="currentColor" stroke="none">WARKOP</text>
      <rect x="530" y="330" width="184" height="50" rx="2"/>
      <rect x="560" y="310" width="22" height="20" rx="3"/>
      <path class="kos-steam" d="M566 304c-5-7 5-11 0-18M576 304c-5-7 5-11 0-18"/>
      <rect x="666" y="296" width="12" height="8"/>
      <rect x="658" y="304" width="28" height="26" rx="6"/>
    </g>

    {{-- kucing kos (tanda tangan kecil) --}}
    <g transform="translate(468 352)">
      <path d="M0 28C-2 14 6 8 14 8C22 8 26 16 24 28Z"/>
      <circle cx="14" cy="4" r="6"/>
      <path d="M9 0L9-6L13-2M19 0L19-6L15-2"/>
      <path d="M24 26C34 26 36 14 30 12"/>
    </g>
  </svg>
</div>
