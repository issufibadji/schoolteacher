{{-- Ilustração flat (sem fundo) do professor no notebook, usada no painel das telas de auth. --}}
<svg {{ $attributes->merge(['viewBox' => '0 0 320 280', 'fill' => 'none', 'xmlns' => 'http://www.w3.org/2000/svg', 'role' => 'img', 'aria-label' => 'Professor estudando no notebook']) }}>
    {{-- sombra no chão --}}
    <ellipse cx="160" cy="272" rx="140" ry="6" fill="#000" fill-opacity=".18" />

    {{-- corpo: blazer, camisa, gravata, lapelas --}}
    <path d="M92 252C92 200 112 176 160 172C208 176 228 200 228 252Z" fill="#EDE4FF" />
    <path d="M146 174L160 206L174 174Z" fill="#fff" />
    <path d="M156 180H164L162 188L167 214L160 222L153 214L158 188Z" fill="#3C0F67" />
    <path d="M146 173L134 192L148 198L140 210L158 236Z" fill="#C9B3F5" />
    <path d="M174 173L186 192L172 198L180 210L162 236Z" fill="#C9B3F5" />

    {{-- pescoço, orelhas, rosto --}}
    <rect x="150" y="148" width="20" height="26" rx="4" fill="#E9B48F" />
    <ellipse cx="125" cy="122" rx="6" ry="9" fill="#F2C7A5" />
    <ellipse cx="195" cy="122" rx="6" ry="9" fill="#F2C7A5" />
    <ellipse cx="160" cy="118" rx="34" ry="40" fill="#F2C7A5" />

    {{-- cabelo --}}
    <path d="M124 116C120 80 144 66 164 68C188 70 200 86 196 116C192 100 182 92 168 92C150 94 136 100 128 120Z" fill="#2B1645" />

    {{-- sobrancelhas, óculos, olhos, nariz, boca --}}
    <path d="M136 104Q145 99 154 104M166 104Q175 99 184 104" stroke="#2B1645" stroke-width="3" stroke-linecap="round" />
    <rect x="134" y="110" width="22" height="16" rx="5" fill="#fff" fill-opacity=".35" stroke="#2B1645" stroke-width="3" />
    <rect x="164" y="110" width="22" height="16" rx="5" fill="#fff" fill-opacity=".35" stroke="#2B1645" stroke-width="3" />
    <path d="M156 117H164" stroke="#2B1645" stroke-width="3" />
    <circle cx="145" cy="118" r="2.5" fill="#2B1645" />
    <circle cx="175" cy="118" r="2.5" fill="#2B1645" />
    <path d="M160 124Q156 132 162 133" stroke="#D99A78" stroke-width="2.5" stroke-linecap="round" />
    <path d="M150 141Q160 149 170 141" stroke="#9F4A54" stroke-width="3" stroke-linecap="round" />

    {{-- notebook (tampa voltada pra quem olha) --}}
    <rect x="96" y="186" width="128" height="64" rx="6" fill="#F8F5FF" stroke="#D9CCF5" stroke-width="2" />
    <circle cx="160" cy="218" r="11" fill="#751AD9" />
    <path d="M160 211L162 216L167 218L162 220L160 225L158 220L153 218L158 216Z" fill="#fff" />
    <rect x="84" y="248" width="152" height="6" rx="3" fill="#CDBDEB" />

    {{-- livros --}}
    <rect x="26" y="198" width="14" height="56" rx="2" fill="#F0ABFC" />
    <rect x="26" y="208" width="14" height="3" fill="#fff" fill-opacity=".6" />
    <rect x="42" y="186" width="12" height="68" rx="2" fill="#fff" />
    <rect x="42" y="196" width="12" height="3" fill="#751AD9" />
    <rect x="56" y="206" width="14" height="48" rx="2" fill="#A78BFA" />
    <rect x="56" y="240" width="14" height="3" fill="#fff" fill-opacity=".6" />
    <rect x="72" y="214" width="10" height="40" rx="2" fill="#3C0F67" />

    {{-- caneca com vapor --}}
    <rect x="250" y="226" width="26" height="28" rx="4" fill="#F472B6" />
    <path d="M276 232H281A6 6 0 0 1 281 246H276" stroke="#F472B6" stroke-width="4" />
    <path d="M257 218Q253 212 258 206M266 218Q262 212 267 206" stroke="#fff" stroke-opacity=".7" stroke-width="2.5" stroke-linecap="round" />

    {{-- mesa --}}
    <rect x="16" y="254" width="288" height="12" rx="3" fill="#2A0B4A" />
</svg>
