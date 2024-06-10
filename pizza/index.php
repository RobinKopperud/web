<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nordkisa Pizza & Grill</title>
  <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
  <div class="container">
  <a class="navbar-brand" href="tel:+46677101">
      <div class="brand-text">466 77 101</div>
    </a>
    <a class="navbar-brand navbar-brand-custom mx-auto" href="#">
      <div class="brand-text">NORDKISA PIZZA & GRILL</div>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link" href="#" onclick="showMenu()">Meny</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#" onclick="showFeedback()">Tilbakemeldinger</a>
        </li>
      </ul>
    </div>
  </div>
</nav>


<!-- Main Container -->
<div class="container">
  <!-- Spinning Pizza Image -->
  <div class="text-center mt-5">
    <img src="pizza.png" id="spinning-pizza" class="img-fluid" alt="Spinning Pizza">
  </div>
  
  <!-- Menu Section -->
<div id="menu-container">
  <h1 class="mt-5 text-center">Vår Meny</h1>
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Margherita</h5>
          <p class="card-text">Tomatsaus, mozzarella, basilikum</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Pepperoni</h5>
          <p class="card-text">Tomatsaus, mozzarella, pepperoni</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Vegetar</h5>
          <p class="card-text">Tomatsaus, mozzarella, diverse grønnsaker</p>
        </div>
      </div>
    </div>
  </div>
  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Hawaiian</h5>
          <p class="card-text">Tomatsaus, mozzarella, skinke, ananas</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Four Seasons</h5>
          <p class="card-text">Tomatsaus, mozzarella, skinke, artisjokker, sopp, oliven</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Diavola</h5>
          <p class="card-text">Tomatsaus, mozzarella, sterk salami, chili</p>
        </div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Margherita</h5>
          <p class="card-text">Tomatsaus, mozzarella, basilikum</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Pepperoni</h5>
          <p class="card-text">Tomatsaus, mozzarella, pepperoni</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Vegetar</h5>
          <p class="card-text">Tomatsaus, mozzarella, diverse grønnsaker</p>
        </div>
      </div>
    </div>
  </div>
  <div class="row mt-4">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Hawaiian</h5>
          <p class="card-text">Tomatsaus, mozzarella, skinke, ananas</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Four Seasons</h5>
          <p class="card-text">Tomatsaus, mozzarella, skinke, artisjokker, sopp, oliven</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title">Diavola</h5>
          <p class="card-text">Tomatsaus, mozzarella, sterk salami, chili</p>
        </div>
      </div>
    </div>
  </div>
</div>

  

  <!-- Feedback Section -->
  <div id="feedback-container" class="feedback-container">
    <h1 class="mt-5">Tilbakemeldinger</h1>
    <form id="feedback-form">
      <div class="form-group">
        <label for="name">Navn</label>
        <input type="text" class="form-control" id="name" name="name" required>
      </div>
      <div class="form-group">
        <label for="feedback">Tilbakemelding</label>
        <textarea class="form-control" id="feedback" name="feedback" rows="4" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Send inn</button>
    </form>
    <div id="feedback-list" class="mt-4">
      <!-- Feedbacks will be displayed here -->
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="bg-light text-center text-lg-start">
  <div class="container p-4">
    <p class="text-center">Kontakt oss på: <a href="mailto:nordkisapizza@gmail.com">nordkisapizza@gmail.com</a></p>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="script.js"></script>
</body>
</html>
