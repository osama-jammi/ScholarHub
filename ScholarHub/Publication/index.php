<?php

include '../Base-donnee/index.php';

$sql = "SELECT id,title,description,genre FROM annonces";
$result = $conn->query($sql);
$annoces = array();
$comments = array();

if ($result->num_rows > 0) {
    // output data of each row
    while ($row = $result->fetch_assoc()) {
        array_push($annoces, $row);
    }

} else {
    echo "0 results";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Publication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
</head>
<style>
<?php include "style.css"?>
</style>

<body>
    <div class="progress-bar">
        <div class="progress-fill"></div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#" style="color: var(--primary-color)">Annonces et PubSpace
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../Home/index.html" style="color: var(--text-color)">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../Publication/index.html"
                            style="color: var(--text-color)">Publication</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../Cours/index.html" style="color: var(--text-color)">Cours</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-sm btn-outline-primary" id="theme-toggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" id="light-element"
        class="light-element1 light-element" style="transform: translate(100px, 100px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element2 light-element"
        style="transform: translate(1000px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element3 light-element"
        style="transform: translate(100px, 900px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element4 light-element"
        style="transform: translate(1500px, 900px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element5 light-element"
        style="transform: translate(1500px, 1000px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element5 light-element"
        style="transform: translate(100px, 500px)" />
    <img src="https://www.code212.ac.ma/img/elements/light-elements.png" alt="" class="light-element5 light-element"
        style="transform: translate(1200px, 600px)" />
    <img src="https://www.code212.ac.ma/img/elements/rocket-element.png" alt="" id="rocket" />

    <main class="container">
        <div class="annonces">
            <div class="skeleton-loader" style="height: 200px"></div>
            <?php
            foreach ($annoces as $annoce) {
                echo '
                <div  id="' . str_replace(" ", "", $annoce["title"]) . '"></div>
                <div ></div>
    <article class="cadre">
        <h2 class="titre-annonce">' . $annoce["title"] . '</h2>
        <div class="annonce-header">
            <img src="./images/image.png" class="user-avatar" />
            <div>
                <h3 class="mb-0">Issam Mouhala</h3>
                <small id="text-muted">Posted 2 hours ago</small>
            </div>
        </div>
        <div class="annonce-content">
            <div id="info">
                <div>
                    <strong>Genre :</strong>
                    <span class="badge bg-info">' . $annoce["genre"] . '</span>
                </div>
                <div>
                    <strong>Cours :</strong>
                    <span class="badge bg-secondary">WEB-Tech</span>
                </div>
            </div>

            <div class="content">
                <i class="fas fa-file-upload fs-4 mb-2"></i>
                <p class="small mb-2" style="width: 610px;;">' . $annoce["description"] . '</p>
            </div>

            <div class="mt-4">
                <h5>Related Links:</h5>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="#" class="btn btn-sm btn-outline-primary">Documentation</a>
                    <a href="#" class="btn btn-sm btn-outline-primary">Tutorial</a>
                    <a href="#" class="btn btn-sm btn-outline-primary">FAQ</a>
                </div>
            </div>
        </div>

        <div class="comment-section">
            <form class="d-flex gap-2" id="add" action="./php/add_comment.php" method="post">
            <input type="hidden" name="id" value="' . $annoce['id'] . '">
                <input type="text" class="form-control" placeholder="Add a comment..." name="content" />
                <input type="submit" id="add_comment" name="comment" class="btn btn-primary">
                    <i class="fas fa-paper-plane btn btn-primary"></i>
            </form>
            <button data-bs-toggle="collapse" data-bs-target="#demo' . $annoce['id'] . '" class="btn btn-primary dropdown-toggle" style="margin-top:7px">Commentaires  </button>
    <div id="demo' . $annoce['id'] . '"  class="collapse demo"  >';
                $sql = "SELECT id,id_user,id_annonce,descreption,likee,deslike FROM comment WHERE id_annonce='" . $annoce['id'] . "'";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // output data of each row
                    while ($row = $result->fetch_assoc()) {
                        array_push($comments, $row);

                    }
                }

                foreach ($comments as $comment) {
                    echo '
              
               <div id="comment-user">
                   <img src="./images/image.png" class="user-avatar" />
                   <div>
                       <div>';
                    $user = array();
                    $sql = "SELECT username,avatar FROM users Where id=" . $comment['id_user'] . "";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        // output data of each row
                        while ($row = $result->fetch_assoc()) {
                            array_push($user, $row);
                        }
                    } else {
                        break;
                    }
                    echo '  @<strong>' . str_replace(" ", "", $user[0]["username"]) . '';
                    echo '
            </strong>
            <small id="text-muted"> 2 hours ago</small>
        </div>
        <div id="comment-content" >
            <p style="    width: 300px;text-wrap:wrap;
            "> ' . $comment["descreption"] . '
            </p>
        </div>
        <form id="reaction-user" action="./php/reaction.php" method="post">
        <input type="hidden" name="id" value="' . $comment['id'] . '">
        <input type="hidden" name="like" value="' . $comment['likee'] . '">
        <input type="hidden" name="deslike" value="' . $comment['deslike'] . '">

            <div id="like">
                <input type="submit" name="like_submit" value=""> <i class="fa-solid fa-thumbs-up"></i>

                <span> ' . $comment["likee"] . '</span>
            </div>
            <div id="deslike">
                <input type="submit" name="deslike_submit" value=""> <i class="fa-solid fa-thumbs-down"></i>
                <span> ' . $comment["deslike"] . '</span>
            </div>
            </form>
        </div>
        </div>


        ';
                }
                echo '

        </div>
        </article>
        ';
                $comments = array();

            }

            ?>




        </div>
    </main>
    <?php
    $sql = "SELECT title FROM annonces limit 3";
    $result = $conn->query($sql);
    $annonces = array();
    if ($result->num_rows > 0) {
        // output data of each row
        while ($row = $result->fetch_assoc()) {
            array_push($annonces, $row);

        }
    }

    ?>
    <aside class="recent-annonce">
        <h5 class="fw-bold mb-3">Recent Announcements</h5>
        <div class="list-group">
            <a href="#<?php echo str_replace(" ", "", $annonces[0]["title"]) ?>"
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                <?php echo $annonces[0]["title"] ?>
                <span class="badge bg-primary rounded-pill">New</span>
            </a>
            <a href="                               #<?php echo str_replace(" ", "", $annonces[1]["title"]); ?>"
                class="list-group-item list-group-item-action">
                <?php echo $annonces[1]["title"] ?>
            </a>
            <a href=" #<?php echo str_replace(" ", "", $annonces[2]["title"]) ?> " class=" list-group-item
                list-group-item-action">
                <?php echo $annonces[2]["title"] ?>
            </a>
        </div>
    </aside>
    <aside class="filter">
        <h5 class="fw-bold mb-3">Filtrer les Annonces</h5>
        <div class="dropdown">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fa-solid fa-filter"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item active" href="#">Par Année </a></li>
                <li><a class="dropdown-item" href="#">par Sujet </a></li>
                <li><a class="dropdown-item" href="#">Par Type </a></li>
            </ul>
        </div>
    </aside>
    <!--
    <div class="fab">
        <i class="fas fa-plus"></i>
    </div>
-->
    <!-- <div class="toast align-items-center position-fixed bottom-0 end-0 p-3" role="alert" aria-live="assertive"
        aria-atomic="true" id="liveToast">
        <div class="d-flex">
            <div class="toast-body">Comment added successfully!</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        </div>
    -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Dark Mode Toggle
    const themeToggle = document.getElementById('theme-toggle');
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        themeToggle.querySelector('i').classList.toggle('fa-sun');
    });

    // Scroll Progress
    window.addEventListener('scroll', () => {
        const progress = document.querySelector('.progress-fill');
        const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (window.scrollY / windowHeight) * 100;
        progress.style.width = scrolled + '%';
    });

    // Toast Initialization

    // Skeleton Loading Simulation
    setTimeout(() => {
        document.querySelector('.skeleton-loader').style.display = 'none';
        document.querySelectorAll('.cadre').forEach(cadre => {
            cadre.style.display = 'inline-block';


        })

    }, 2000);
    </script>
</body>

</html>
<?php
$conn->close();

?>