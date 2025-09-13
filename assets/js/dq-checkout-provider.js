// Renders provider iFrame (Clover for now) and posts token to server.
// No raw card data touches your server.

(function(){
  if (!window.DQ_PROVIDER || !window.DQ_SUB_ENDPOINT) return;

  // Only init on product/checkout pages that have subscription plan
  const root = document.querySelector('[data-dq-subscribe]');
  if (!root) return;

  if (DQ_PROVIDER === 'clover') {
    const clover = new Clover(window.DQ_CLOVER_PUBLIC);
    const elements = clover.elements();
    const card = elements.create('card');
    const mountEl = document.getElementById('dq-clover-card');
    if (mountEl) card.mount('#dq-clover-card');

    document.getElementById('dqSubBtn')?.addEventListener('click', async () => {
      try {
        const res = await clover.createToken();
        if (!res || !res.token) throw new Error('Tokenization failed');

        const payload = {
          plan_key: root.dataset.planKey,
          amount_cents: parseInt(root.dataset.amountCents || '0',10),
          interval: root.dataset.interval || 'MONTH',
          interval_count: parseInt(root.dataset.intervalCount || '1',10),
          source: res.token
        };

        const r = await fetch(DQ_SUB_ENDPOINT, {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(payload),
          credentials: 'same-origin'
        });
        const out = await r.json();
        if (!r.ok || !out.ok) throw new Error(out?.message || 'Subscription failed');
        alert('Subscription created successfully!');
        // redirect if needed
      } catch (e) {
        alert(e.message);
        console.error(e);
      }
    });
  }
})();
