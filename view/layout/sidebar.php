<?php
// Obtener la URL actual
$actual = $_GET['url'] ?? '';
?>

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- CLIENTES -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($actual, 'cliente') === 0) ? '' : 'collapsed' ?>" 
         href="/mi_garita/index.php?url=cliente/index">
        <i class="bi bi-people"></i>
        <span>Clientes</span>
      </a>
    </li>

    <!-- ADMINISTRADORES -->
    <li class="nav-item">
      <a class="nav-link <?= (strpos($actual, 'user') === 0) ? '' : 'collapsed' ?>"
         href="/mi_garita/index.php?url=user/index">
        <i class="bi bi-person-gear"></i>
        <span>Administradores</span>
      </a>
    </li>

  </ul>

</aside>
<!-- End Sidebar -->
