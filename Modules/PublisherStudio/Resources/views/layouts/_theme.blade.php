<style>
  :root{
    --ink:#0b0a0f; --panel:#141019; --panel2:#1b1522; --line:rgba(255,255,255,.08);
    --txt:#f4f2f7; --mut:#9a95a3; --brand:#7c4dff; --brand2:#b388ff; --ok:#2fbf71; --warn:#f5a623; --bad:#ff5470;
    --radius:16px;
  }
  *{box-sizing:border-box}
  body{margin:0;background:radial-gradient(1200px 600px at 80% -10%,rgba(124,77,255,.18),transparent),var(--ink);color:var(--txt);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;}
  a{color:var(--brand2);text-decoration:none}
  .gk-wrap{max-width:1120px;margin:0 auto;padding:0 20px}
  .gk-brand{display:flex;align-items:center;gap:.6rem;font-weight:800;letter-spacing:-.02em}
  .gk-brand .dot{width:.7rem;height:.7rem;border-radius:50%;background:linear-gradient(135deg,var(--brand),var(--brand2));box-shadow:0 0 18px var(--brand)}
  .gk-brand small{display:block;font-weight:600;font-size:.62rem;letter-spacing:.22em;text-transform:uppercase;color:var(--mut)}
  .gk-btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:2rem;padding:.7rem 1.3rem;font-weight:700;font-size:.92rem;cursor:pointer;background:linear-gradient(135deg,var(--brand),#5c33d6);color:#fff;transition:transform .15s,box-shadow .15s;box-shadow:0 10px 30px rgba(124,77,255,.35)}
  .gk-btn:hover{transform:translateY(-1px);color:#fff}
  .gk-btn.ghost{background:transparent;border:1px solid var(--line);color:var(--txt);box-shadow:none}
  .gk-btn.sm{padding:.45rem .9rem;font-size:.82rem}
  .gk-btn.block{width:100%}
  .gk-card{background:linear-gradient(180deg,var(--panel),var(--panel2));border:1px solid var(--line);border-radius:var(--radius);padding:1.4rem}
  .gk-input,.gk-select,.gk-text{width:100%;background:#0f0c15;border:1px solid var(--line);border-radius:12px;color:var(--txt);padding:.75rem .9rem;font-size:.95rem;outline:none;transition:border-color .15s,box-shadow .15s}
  .gk-input:focus,.gk-select:focus,.gk-text:focus{border-color:var(--brand);box-shadow:0 0 0 3px rgba(124,77,255,.2)}
  .gk-label{display:block;font-size:.75rem;letter-spacing:.08em;text-transform:uppercase;color:var(--mut);margin:0 0 .4rem}
  .gk-field{margin-bottom:1rem}
  .gk-badge{display:inline-block;padding:.2rem .6rem;border-radius:2rem;font-size:.72rem;font-weight:700;letter-spacing:.04em}
  .b-secondary{background:rgba(154,149,163,.18);color:#cfc9d6}
  .b-info{background:rgba(66,165,245,.18);color:#8ecbff}
  .b-warning{background:rgba(245,166,35,.18);color:#ffcf80}
  .b-success{background:rgba(47,191,113,.2);color:#7de3aa}
  .b-danger{background:rgba(255,84,112,.2);color:#ff9db0}
  .gk-alert{border-radius:12px;padding:.85rem 1.1rem;margin-bottom:1.2rem;font-size:.92rem;border:1px solid var(--line)}
  .gk-alert.ok{background:rgba(47,191,113,.12);border-color:rgba(47,191,113,.35)}
  .gk-alert.err{background:rgba(255,84,112,.12);border-color:rgba(255,84,112,.35)}
  .gk-muted{color:var(--mut)}
  .gk-grid{display:grid;gap:1rem}
  @media(min-width:720px){.gk-grid.cols-2{grid-template-columns:1fr 1fr}.gk-grid.cols-4{grid-template-columns:repeat(4,1fr)}}
  .gk-stat{background:linear-gradient(180deg,var(--panel),var(--panel2));border:1px solid var(--line);border-radius:var(--radius);padding:1.1rem 1.2rem}
  .gk-stat b{display:block;font-size:1.8rem;letter-spacing:-.02em}
  .gk-stat span{font-size:.78rem;text-transform:uppercase;letter-spacing:.1em;color:var(--mut)}
  .gk-table{width:100%;border-collapse:collapse}
  .gk-table th{text-align:left;font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--mut);padding:.6rem .7rem;border-bottom:1px solid var(--line)}
  .gk-table td{padding:.8rem .7rem;border-bottom:1px solid var(--line);font-size:.92rem;vertical-align:middle}
  .gk-error{color:var(--bad);font-size:.82rem;margin-top:.35rem}
  .gk-item-row{display:grid;grid-template-columns:1.2fr 2fr .8fr auto;gap:.6rem;margin-bottom:.6rem;align-items:center}
  @media(max-width:640px){.gk-item-row{grid-template-columns:1fr}}
  .gk-chip{display:inline-flex;gap:.4rem;align-items:center;padding:.3rem .8rem;border:1px solid var(--line);border-radius:2rem;font-size:.82rem;color:var(--mut);cursor:pointer}
  .gk-chip.active{border-color:var(--brand);color:var(--txt);background:rgba(124,77,255,.15)}
</style>
