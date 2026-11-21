<svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
  <defs>
    <linearGradient id="srwCompBg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#10b981" />
      <stop offset="55%" stop-color="#059669" />
      <stop offset="100%" stop-color="#047857" />
    </linearGradient>
    <linearGradient id="srwCompGold" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#fde047" />
      <stop offset="50%" stop-color="#fbbf24" />
      <stop offset="100%" stop-color="#d97706" />
    </linearGradient>
    <linearGradient id="srwCompShine" x1="0%" y1="0%" x2="0%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.3" />
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0.0" />
    </linearGradient>
  </defs>

  <!-- Squircle Base -->
  <rect x="28" y="28" width="456" height="456" rx="120" fill="url(#srwCompBg)" />
  <rect x="30" y="30" width="452" height="452" rx="118" fill="none" stroke="#6ee7b7" stroke-width="3" stroke-opacity="0.45" />

  <!-- Glass Light Reflection -->
  <path d="M 28 148 C 28 81.7 81.7 28 148 28 L 364 28 C 430.3 28 484 81.7 484 148 L 484 210 C 370 170 210 260 28 200 Z" fill="url(#srwCompShine)" />

  <!-- Smart Signal Arcs -->
  <g fill="none" stroke-linecap="round">
    <path d="M 180 185 A 145 145 0 0 1 332 185" stroke="#a7f3d0" stroke-width="16" opacity="0.85" />
    <path d="M 215 210 A 100 100 0 0 1 297 210" stroke="#ffffff" stroke-width="15" opacity="0.95" />
  </g>

  <!-- Community Roof -->
  <path d="M 256 182 L 396 288 L 356 322 L 256 244 L 156 322 L 116 288 Z" fill="#ffffff" />

  <!-- Symmetrical Gateway Pillars -->
  <path d="M 166 332 L 214 294 L 214 400 L 166 400 Z" fill="#ffffff" fill-opacity="0.95" />
  <path d="M 298 294 L 346 332 L 346 400 L 298 400 Z" fill="#ffffff" fill-opacity="0.85" />

  <!-- Foundation -->
  <rect x="146" y="412" width="220" height="22" rx="11" fill="#a7f3d0" />

  <!-- Core Smart Diamond -->
  <polygon points="256,298 290,340 256,382 222,340" fill="url(#srwCompGold)" />
  <polygon points="256,314 274,340 256,366 238,340" fill="#ffffff" opacity="0.75" />
</svg>
