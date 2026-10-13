{{-- Pagination for a list of evaluation responses, one respondent per page.
     Expects $responses; each response card on the page must have the
     "resp-card" class, in the same order as $responses. Client-side only. --}}
@if($responses->count() > 1)
<div id="respPager" class="no-print" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px">
  <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
    <button type="button" class="btn btn-outline btn-sm" id="respPrev"><i class="fas fa-chevron-left"></i> Previous</button>
    <div id="respNums" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap"></div>
    <button type="button" class="btn btn-outline btn-sm" id="respNext">Next <i class="fas fa-chevron-right"></i></button>
  </div>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <span id="respCount" style="font-size:12px;color:var(--gray-400)"></span>
    <select id="respJump" class="form-control" style="width:220px" aria-label="Jump to participant">
      @foreach($responses as $i => $r)
      <option value="{{ $i }}">{{ $r->beneficiary->first_name }} {{ $r->beneficiary->last_name }}</option>
      @endforeach
    </select>
  </div>
</div>
<script>
(function () {
  var cards = Array.prototype.slice.call(document.querySelectorAll('.resp-card'));
  var total = cards.length;
  if (total < 2) return;
  var cur = 0;
  var nums = document.getElementById('respNums');

  function pageList() {
    var out = [], last = 0;
    for (var p = 0; p < total; p++) {
      if (p === 0 || p === total - 1 || Math.abs(p - cur) <= 1) {
        if (p - last > 1) out.push('...');
        out.push(p);
        last = p;
      }
    }
    return out;
  }

  function show(i) {
    cur = Math.max(0, Math.min(total - 1, i));
    cards.forEach(function (c, idx) { c.style.display = idx === cur ? '' : 'none'; });
    document.getElementById('respPrev').disabled = cur === 0;
    document.getElementById('respNext').disabled = cur === total - 1;
    document.getElementById('respCount').textContent = 'Response ' + (cur + 1) + ' of ' + total;
    document.getElementById('respJump').value = String(cur);
    nums.innerHTML = '';
    pageList().forEach(function (p) {
      if (p === '...') {
        var dots = document.createElement('span');
        dots.textContent = '…';
        dots.style.color = 'var(--gray-400)';
        nums.appendChild(dots);
        return;
      }
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm ' + (p === cur ? 'btn-primary' : 'btn-outline');
      b.textContent = p + 1;
      b.onclick = function () { show(p); };
      nums.appendChild(b);
    });
  }

  document.getElementById('respPrev').onclick = function () { show(cur - 1); };
  document.getElementById('respNext').onclick = function () { show(cur + 1); };
  document.getElementById('respJump').onchange = function () { show(parseInt(this.value, 10)); };
  show(0);
})();
</script>
@endif
