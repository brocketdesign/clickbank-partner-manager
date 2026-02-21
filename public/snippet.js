/**
 * Partner Offer Popup Widget v2.0.0
 * - Validates domain before displaying
 * - Stage 1: Non-intrusive bottom popup (3s delay, 20s display)
 * - Stage 2: Full-screen overlay with skip timer if Stage 1 not clicked
 * - Uses Shadow DOM for style isolation
 * - Cookie-based persistence to prevent repeat popups
 */
(function() {
  'use strict';

  const DEBUG_PREFIX = '[PartnerPopup]';
  const log = (...args) => console.log(DEBUG_PREFIX, ...args);
  const warn = (...args) => console.warn(DEBUG_PREFIX, ...args);
  const error = (...args) => console.error(DEBUG_PREFIX, ...args);

  log('Partner popup widget version: v2.0.0');

  // Get script element and partner ID
  const script = document.currentScript || (function() {
    const s = document.getElementsByTagName('script');
    return s[s.length - 1];
  })();

  if (!script) {
    warn('Script element not found');
    return;
  }

  const partner = script.getAttribute('data-partner');
  if (!partner) {
    warn('Missing data-partner attribute');
    return;
  }

  // Configuration
  const CONFIG = {
    API_BASE: 'https://adeasynow.com',
    COOKIE_PREFIX: 'partner-popup-',
    COOKIE_EXPIRY_HOURS: 24,
    STAGE1_DELAY_MS: 3000,
    STAGE1_DISPLAY_MS: 20000,
    STAGE2_SKIP_MS: 5000
  };

  // Generic preset for partner offers
  const OFFER_PRESET = {
    headline: 'Exclusive Deal from Our Partners!',
    subheadline: 'Limited time offer - Don\'t miss out on this amazing deal',
    ctaText: 'View Offer',
    skipText: 'Skip',
    accentHex: '#3b82f6',
    logoUrl: 'https://adeasynow.com/assets/img/gift-icon.svg'
  };

  // State management
  const state = {
    currentCreative: null,
    stage1Root: null,
    stage1Shadow: null,
    stage1TimeoutId: null,
    stage1DismissTimeoutId: null,
    stage1CountdownInterval: null,
    stage2Root: null,
    stage2Shadow: null,
    stage2SkipTimeoutId: null,
    interacted: false,
    processingInteraction: false
  };

  // Domain validation - checks if current domain is valid
  function isValidDomain() {
    const hostname = window.location.hostname;
    
    // Block localhost and common development domains
    const invalidDomains = [
      'localhost',
      '127.0.0.1',
      '0.0.0.0',
      '.local',
      '.test',
      '.dev'
    ];

    for (const invalid of invalidDomains) {
      if (hostname === invalid || hostname.endsWith(invalid)) {
        log('Invalid domain detected:', hostname);
        return false;
      }
    }

    // Must have at least one dot (basic domain check)
    if (!hostname.includes('.')) {
      log('Domain missing TLD:', hostname);
      return false;
    }

    log('Domain validated:', hostname);
    return true;
  }

  // CMP consent check (GDPR compliance)
  function hasConsent(callback) {
    if (typeof window.__tcfapi === 'function') {
      try {
        window.__tcfapi('getTCData', 2, function(tcData) {
          const purposes = (tcData && tcData.purpose && tcData.purpose.consents) || {};
          callback(!!purposes[1]);
        });
      } catch (e) {
        callback(false);
      }
    } else {
      // No CMP present, assume consent in non-EU contexts
      callback(true);
    }
  }

  // Cookie helpers
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return undefined;
  }

  function setCookie(name, value, options = {}) {
    let cookieString = `${name}=${value}`;
    if (options.expires) {
      const date = new Date();
      date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      cookieString += `; expires=${date.toUTCString()}`;
    }
    cookieString += `; path=${options.path || '/'}`;
    cookieString += '; SameSite=Lax';
    document.cookie = cookieString;
  }

  function hasOpened(creativeId) {
    const cookieKey = CONFIG.COOKIE_PREFIX + creativeId;
    return Boolean(getCookie(cookieKey));
  }

  function markOpened(creativeId) {
    const cookieKey = CONFIG.COOKIE_PREFIX + creativeId;
    setCookie(cookieKey, 'true', { expires: CONFIG.COOKIE_EXPIRY_HOURS / 24, path: '/' });
  }

  // API calls
  async function fetchConfig() {
    const domain = window.location.hostname;
    const url = `${CONFIG.API_BASE}/api/snippet/config?partner=${encodeURIComponent(partner)}&domain=${encodeURIComponent(domain)}`;
    const res = await fetch(url, { method: 'GET', credentials: 'omit', headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Config fetch failed');
    return res.json();
  }

  function chooseCreative(creatives) {
    if (!creatives || creatives.length === 0) return null;
    
    // Filter out already shown creatives
    const available = creatives.filter(c => !hasOpened(c.id));
    if (available.length === 0) {
      log('All creatives already shown');
      return null;
    }

    const total = available.reduce((sum, c) => sum + (parseInt(c.weight) || 1), 0);
    const rand = Math.random() * total;
    let acc = 0;
    
    for (const creative of available) {
      acc += (parseInt(creative.weight) || 1);
      if (rand <= acc) return creative;
    }
    return available[0];
  }

  function postImpression(creativeId) {
    try {
      const url = `${CONFIG.API_BASE}/api/metrics/impression`;
      const payload = new URLSearchParams({ partner: partner, creative_id: creativeId });
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, payload);
      } else {
        fetch(url, { method: 'POST', credentials: 'omit', body: payload }).catch(() => {});
      }
    } catch (e) {
      warn('Failed to post impression', e);
    }
  }

  function buildRedirectUrl(creative) {
    return `${CONFIG.API_BASE}/r?aff_id=${encodeURIComponent(partner)}&c=${encodeURIComponent(creative.id)}`;
  }

  // Background open - opens current page in background, redirects current to offer
  function backgroundOpen(creative) {
    const targetUrl = buildRedirectUrl(creative);
    
    try {
      log('backgroundOpen triggered', { creativeId: creative.id, targetUrl });
      // Open current page in a new background tab
      window.open(window.location.href.split('?')[0], '_blank');
    } catch (err) {
      warn('Unable to open background tab', err);
    }

    // Redirect current window to offer
    window.location.href = targetUrl;
  }

  // Color utility
  function shadeColor(color, percent) {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const r = (num >> 16) + amt;
    const g = ((num >> 8) & 0x00FF) + amt;
    const b = (num & 0x0000FF) + amt;
    return `#${(
      0x1000000 +
      (r < 255 ? (r < 0 ? 0 : r) : 255) * 0x10000 +
      (g < 255 ? (g < 0 ? 0 : g) : 255) * 0x100 +
      (b < 255 ? (b < 0 ? 0 : b) : 255)
    ).toString(16).slice(1)}`;
  }

  // Stage 1: Bottom popup (non-intrusive)
  function renderStage1(creative) {
    const host = document.createElement('div');
    host.id = 'partner-popup-stage1-host';
    host.style.cssText = 'position:fixed;left:20px;bottom:20px;z-index:2147483647;width:auto;pointer-events:none;';
    document.body.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });
    const accent = OFFER_PRESET.accentHex;

    const style = document.createElement('style');
    style.textContent = `
      :host { all: initial; }
      *, *::before, *::after { box-sizing: border-box; }
      .popup-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0;
        pointer-events: auto;
        max-width: 380px;
        animation: slideIn 0.3s ease-out;
      }
      @keyframes slideIn {
        from { transform: translateY(100px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
      }
      .progress-bar {
        width: 100%;
        height: 4px;
        background: #e5e7eb;
        border-radius: 9999px 9999px 0 0;
        overflow: hidden;
      }
      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, ${accent}, ${shadeColor(accent, -15)});
        width: 100%;
        animation: depleteBar 20s linear forwards;
      }
      @keyframes depleteBar {
        from { width: 100%; }
        to { width: 0%; }
      }
      .cta-card {
        display: flex;
        align-items: center;
        background: #ffffff;
        border-radius: 0 0 16px 16px;
        padding: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        cursor: pointer;
      }
      .cta-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.35);
      }
      .cta-card .icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, ${accent}20, ${accent}40);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 14px;
        flex-shrink: 0;
        font-size: 20px;
      }
      .cta-info {
        display: flex;
        flex-direction: column;
        margin-right: 14px;
        min-width: 0;
        flex: 1;
      }
      .cta-info h4 {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 600;
        font-size: 14px;
        color: #1f2937;
        margin: 0;
        line-height: 1.4;
      }
      .cta-info .countdown {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 500;
        font-size: 12px;
        color: ${accent};
        margin-top: 4px;
      }
      .cta-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
      }
      .cta-primary {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 600;
        font-size: 13px;
        color: #ffffff;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        border: none;
        background: linear-gradient(135deg, ${accent}, ${shadeColor(accent, -15)});
        transition: transform 0.15s ease, box-shadow 0.2s ease;
      }
      .cta-primary:hover {
        transform: scale(1.03);
        box-shadow: 0 8px 16px ${accent}40;
      }
      .cta-close {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 16px;
        transition: background 0.2s ease, color 0.2s ease;
      }
      .cta-close:hover {
        background: #f3f4f6;
        color: #374151;
      }
      .bounce {
        animation: bounce 2s infinite;
      }
      @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
      }
      @media (max-width: 480px) {
        .popup-wrapper { max-width: calc(100vw - 40px); }
        .cta-card { flex-direction: column; align-items: flex-start; }
        .cta-info { margin-right: 0; margin-bottom: 12px; }
        .cta-actions { width: 100%; justify-content: space-between; }
      }
    `;

    shadow.appendChild(style);

    const wrapper = document.createElement('div');
    wrapper.className = 'popup-wrapper';

    const progressBar = document.createElement('div');
    progressBar.className = 'progress-bar';
    progressBar.innerHTML = '<div class="progress-fill"></div>';
    wrapper.appendChild(progressBar);

    const card = document.createElement('div');
    card.className = 'cta-card bounce';

    const creativeName = creative.name || OFFER_PRESET.headline;

    card.innerHTML = `
      <div class="icon">🎁</div>
      <div class="cta-info">
        <h4>${creativeName}</h4>
        <div class="countdown">Limited time • <span id="timer">20</span>s remaining</div>
      </div>
      <div class="cta-actions">
        <button class="cta-primary" type="button">${OFFER_PRESET.ctaText}</button>
        <button class="cta-close" type="button" aria-label="Close">×</button>
      </div>
    `;

    const primaryBtn = card.querySelector('.cta-primary');
    const closeBtn = card.querySelector('.cta-close');
    const timerDisplay = card.querySelector('#timer');

    // Countdown timer
    let remainingTime = 20;
    state.stage1CountdownInterval = window.setInterval(() => {
      remainingTime--;
      if (timerDisplay) timerDisplay.textContent = remainingTime;
      if (remainingTime <= 0 && state.stage1CountdownInterval) {
        window.clearInterval(state.stage1CountdownInterval);
      }
    }, 1000);

    const handleAction = (e) => {
      if (state.processingInteraction) return;
      state.processingInteraction = true;
      e.preventDefault();
      e.stopPropagation();
      
      log('Stage 1: User interaction');
      state.interacted = true;
      markOpened(creative.id);
      dismissStage1();
      dismissStage2();
      backgroundOpen(creative);
    };

    primaryBtn.addEventListener('click', handleAction);
    closeBtn.addEventListener('click', handleAction);
    card.addEventListener('click', handleAction);

    wrapper.appendChild(card);
    shadow.appendChild(wrapper);

    state.stage1Root = host;
    state.stage1Shadow = shadow;
  }

  function dismissStage1() {
    if (state.stage1CountdownInterval) {
      window.clearInterval(state.stage1CountdownInterval);
      state.stage1CountdownInterval = null;
    }
    if (state.stage1DismissTimeoutId) {
      window.clearTimeout(state.stage1DismissTimeoutId);
      state.stage1DismissTimeoutId = null;
    }
    if (state.stage1Root && state.stage1Root.parentNode) {
      state.stage1Root.parentNode.removeChild(state.stage1Root);
    }
    state.stage1Root = null;
    state.stage1Shadow = null;
  }

  // Stage 2: Full-screen overlay (intrusive fallback)
  function renderStage2(creative) {
    const overlay = document.createElement('div');
    overlay.id = 'partner-popup-stage2-overlay';
    overlay.style.cssText = `
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.75);
      z-index: 2147483646;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease-out;
    `;
    document.body.appendChild(overlay);

    const host = document.createElement('div');
    host.id = 'partner-popup-stage2-host';
    host.style.cssText = 'position:relative;z-index:2147483647;width:auto;max-width:90vw;';
    overlay.appendChild(host);

    const shadow = host.attachShadow({ mode: 'open' });
    const accent = OFFER_PRESET.accentHex;

    const style = document.createElement('style');
    style.textContent = `
      :host { all: initial; }
      *, *::before, *::after { box-sizing: border-box; }
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes scaleIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
      }
      .modal {
        background: #ffffff;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 50px 100px -20px rgba(0, 0, 0, 0.4);
        text-align: center;
        max-width: 420px;
        animation: scaleIn 0.3s ease-out;
      }
      .modal .icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, ${accent}20, ${accent}40);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 32px;
      }
      .modal h2 {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 700;
        font-size: 22px;
        color: #1f2937;
        margin: 0 0 10px;
        line-height: 1.3;
      }
      .modal p {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 400;
        font-size: 15px;
        color: #6b7280;
        margin: 0 0 28px;
        line-height: 1.5;
      }
      .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
      }
      .modal-skip {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 500;
        font-size: 14px;
        color: #9ca3af;
        padding: 12px 24px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .modal-skip:hover:not(:disabled) {
        background: #f3f4f6;
        color: #6b7280;
      }
      .modal-skip:disabled {
        opacity: 0.6;
        cursor: not-allowed;
      }
      .modal-primary {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-weight: 600;
        font-size: 15px;
        color: #ffffff;
        padding: 12px 28px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, ${accent}, ${shadeColor(accent, -15)});
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .modal-primary:hover {
        transform: scale(1.03);
        box-shadow: 0 10px 25px ${accent}40;
      }
      .countdown-text {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        font-size: 12px;
        color: #9ca3af;
        margin-top: 12px;
      }
      @media (max-width: 480px) {
        .modal { padding: 28px 20px; border-radius: 16px; }
        .modal h2 { font-size: 18px; }
        .modal p { font-size: 14px; }
        .modal-actions { flex-direction: column; }
      }
    `;

    shadow.appendChild(style);

    const creativeName = creative.name || OFFER_PRESET.headline;

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="icon">🎁</div>
      <h2>${creativeName}</h2>
      <p>${OFFER_PRESET.subheadline}</p>
      <div class="modal-actions">
        <button class="modal-skip" type="button" id="skip-btn" disabled>${OFFER_PRESET.skipText} (5)</button>
        <button class="modal-primary" type="button" id="cta-btn">${OFFER_PRESET.ctaText}</button>
      </div>
      <div class="countdown-text" id="countdown">You can skip in 5 seconds</div>
    `;

    shadow.appendChild(modal);
    state.stage2Root = overlay;
    state.stage2Shadow = shadow;

    const skipBtn = modal.querySelector('#skip-btn');
    const ctaBtn = modal.querySelector('#cta-btn');
    const countdown = modal.querySelector('#countdown');

    let skipCountdown = 5;

    const updateCountdown = () => {
      skipCountdown--;
      if (skipCountdown <= 0) {
        skipBtn.disabled = false;
        skipBtn.textContent = OFFER_PRESET.skipText;
        countdown.textContent = '';
      } else {
        skipBtn.textContent = `${OFFER_PRESET.skipText} (${skipCountdown})`;
        countdown.textContent = `You can skip in ${skipCountdown} seconds`;
        state.stage2SkipTimeoutId = window.setTimeout(updateCountdown, 1000);
      }
    };

    state.stage2SkipTimeoutId = window.setTimeout(updateCountdown, 1000);

    const handleSkip = () => {
      if (state.processingInteraction) return;
      state.processingInteraction = true;
      
      log('Stage 2: User skipped - triggering backgroundOpen');
      state.interacted = true;
      markOpened(creative.id);
      dismissStage2();
      backgroundOpen(creative);
    };

    const handleCTA = (e) => {
      if (state.processingInteraction) return;
      state.processingInteraction = true;
      e.preventDefault();
      e.stopPropagation();
      
      log('Stage 2: User clicked CTA');
      state.interacted = true;
      markOpened(creative.id);
      dismissStage2();
      backgroundOpen(creative);
    };

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) handleCTA(e);
    });

    skipBtn.addEventListener('click', handleSkip);
    ctaBtn.addEventListener('click', handleCTA);
  }

  function dismissStage2() {
    if (state.stage2SkipTimeoutId) {
      window.clearTimeout(state.stage2SkipTimeoutId);
      state.stage2SkipTimeoutId = null;
    }
    if (state.stage2Root && state.stage2Root.parentNode) {
      state.stage2Root.parentNode.removeChild(state.stage2Root);
    }
    state.stage2Root = null;
    state.stage2Shadow = null;
  }

  // Schedule popup display
  function scheduleStage1(creative) {
    state.currentCreative = creative;
    postImpression(creative.id);

    log('Scheduling Stage 1 display', { delay: CONFIG.STAGE1_DELAY_MS, creativeId: creative.id });

    state.stage1TimeoutId = window.setTimeout(() => {
      log('Displaying Stage 1 popup');
      renderStage1(creative);

      // Auto-dismiss and show Stage 2
      state.stage1DismissTimeoutId = window.setTimeout(() => {
        if (!state.interacted) {
          log('Stage 1 timeout - showing Stage 2');
          dismissStage1();
          renderStage2(creative);
        }
      }, CONFIG.STAGE1_DISPLAY_MS);
    }, CONFIG.STAGE1_DELAY_MS);
  }

  // Main initialization
  function init() {
    // Domain validation
    if (!isValidDomain()) {
      warn('Popup disabled for invalid domain');
      return;
    }

    hasConsent(function(hasPersonalConsent) {
      log('Consent check complete', { hasPersonalConsent });

      (async function() {
        try {
          const data = await fetchConfig();
          if (!data || !data.success) {
            log('Config fetch unsuccessful');
            return;
          }

          const cfg = data.config || {};
          const creatives = cfg.creatives || [];
          const creative = chooseCreative(creatives);

          if (!creative) {
            log('No eligible creatives available');
            return;
          }

          log('Selected creative', { id: creative.id, name: creative.name });
          scheduleStage1(creative);

        } catch (e) {
          error('Initialization failed', e);
        }
      })();
    });
  }

  // Run on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();