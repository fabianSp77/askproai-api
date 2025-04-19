<header class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="/dashboard">AskProAI</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}" href="/dashboard">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('calls*') ? 'active' : '' }}" href="/calls">Anrufe</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->is('customers*') ? 'active' : '' }}" href="/customers">Kunden</a>
        </li>
      </ul>
    </div>
  </div>
</header>
