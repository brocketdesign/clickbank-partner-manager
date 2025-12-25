(function(){
  // Lightweight snippet for partner ads
  var script = document.currentScript || (function(){var s = document.getElementsByTagName('script'); return s[s.length-1];})();
  if(!script) return;

  var partner = script.getAttribute('data-partner');
  if (!partner) return;

  // Simple CMP check: attempt to call __tcfapi if available to determine consent
  function hasConsent(cb) {
    if (typeof window.__tcfapi === 'function') {
      try {
        window.__tcfapi('getTCData', 2, function(tcData, success){
          try {
            var purposes = tcData && tcData.purpose && tcData.purpose.consents;
            // require purpose 1 (storage) consent for personalized ads
            var consent = purposes && purposes[1];
            cb(!!consent);
          } catch(e){ cb(false); }
        });
      } catch(e) { cb(false); }
    } else {
      // No CMP - treat as non-personal allowed
      cb(false);
    }
  }

  function fetchConfig(cb) {
    var url = 'https://adeasynow.com/api/snippet/config?partner=' + encodeURIComponent(partner);
    fetch(url, {method:'GET', credentials:'include', headers:{'Accept':'application/json'}})
      .then(function(res){
        if (!res.ok) throw new Error('config fetch failed');
        return res.json();
      }).then(function(json){ cb(null, json); })
      .catch(function(err){ cb(err); });
  }

  function chooseCreative(creatives) {
    if (!creatives || creatives.length === 0) return null;
    var total = creatives.reduce(function(sum,c){return sum + (parseInt(c.weight)||0);},0);
    var rand = Math.random()*total;
    var acc = 0;
    for (var i=0;i<creatives.length;i++){
      acc += (parseInt(creatives[i].weight)||0);
      if (rand <= acc) return creatives[i];
    }
    return creatives[0];
  }

  function postImpression(creative_id){
    try{
      fetch('https://adeasynow.com/api/metrics/impression', {
        method: 'POST',
        credentials: 'include',
        body: new URLSearchParams({partner: partner, creative_id: creative_id})
      }).catch(function(){});
    } catch(e){}
  }

  function attachClickHandler(container, creative) {
    container.addEventListener('click', function(e){
      var el = e.target;
      while (el && el !== container) {
        if (el.tagName && el.tagName.toLowerCase() === 'a') {
          var href = el.getAttribute('href');
          // Replace with redirect to adeasynow to preserve attribution
          var r = 'https://adeasynow.com/r?aff_id=' + encodeURIComponent(partner) + '&c=' + encodeURIComponent(creative.id);
          // Optionally include return param
          // Navigate to our short redirect which logs and forwards to hoplink
          e.preventDefault();
          window.location.href = r;
          return;
        }
        el = el.parentNode;
      }
    });
  }

  // Main flow
  hasConsent(function(personal){
    // personal = true if consent to personalized ads
    fetchConfig(function(err, data){
      if (err || !data || !data.success) return;
      var cfg = data.config || {};
      var creatives = cfg.creatives || [];
      var creative = chooseCreative(creatives);
      if (!creative) return;

      // Render into selectors
      var selectors = cfg.selectors || ['body'];
      selectors.forEach(function(sel){
        try{
          var nodes = document.querySelectorAll(sel);
          if (!nodes || nodes.length === 0) return;
          var node = nodes[0];
          var wrapper = document.createElement('div');
          wrapper.className = 'cb-snippet cb-creative-' + creative.id;
          wrapper.innerHTML = creative.html || ('<a href="' + (creative.destination_hoplink||'#') + '" target="_blank">Visit</a>');
          // Rewrite anchors so they always point to AdeasyNow redirect (works when snippet is embedded on any domain)
          (function(){
            var anchors = wrapper.getElementsByTagName('a');
            for (var j=0;j<anchors.length;j++){
              try {
                var a = anchors[j];
                var orig = a.href; // resolved absolute URL by browser
                if (!orig) continue;
                var r = 'https://adeasynow.com/r?aff_id=' + encodeURIComponent(partner) + '&c=' + encodeURIComponent(creative.id) + '&u=' + encodeURIComponent(orig);
                a.setAttribute('href', r);
                // Ensure it opens in a new tab and is safe
                if (!a.getAttribute('target')) a.setAttribute('target','_blank');
                var rel = a.getAttribute('rel') || '';
                if (rel.indexOf('noopener') === -1) rel = rel ? rel + ' noopener' : 'noopener';
                a.setAttribute('rel', rel.trim());
              } catch(e){}
            }
          })();
          node.appendChild(wrapper);
          // Track impression
          postImpression(creative.id);
          // Attach click handler to reroute clicks to /r
          attachClickHandler(wrapper, creative);
        }catch(e){}
      });
    });
  });
})();