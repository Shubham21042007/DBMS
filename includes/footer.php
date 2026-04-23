  </div><!-- /page-body -->
</div><!-- /main-content -->

<div id="toast-container"></div>

<script src="/DBMS/assets/js/main.js"></script>

<!-- Live clock top bar -->
<script>
  (function() {
    const el1 = document.getElementById('live-clock');
    const el2 = document.getElementById('live-clock-top');
    function tick() {
      const t = new Date().toLocaleTimeString('en-IN', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
      if(el1) el1.textContent = t;
      if(el2) el2.textContent = new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'}) + ' ' + t;
    }
    tick(); setInterval(tick,1000);
  })();
</script>
</body>
</html>
