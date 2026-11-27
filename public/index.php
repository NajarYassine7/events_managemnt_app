<?php

declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$user = current_user();

// Récupération sécurisée de quelques évènements à la une depuis la base
$featuredEvents = [];
try {
    $stmt = db()->query('SELECT id, title, description, starts_at FROM events ORDER BY starts_at ASC LIMIT 3');
    if ($stmt !== false) {
        $featuredEvents = $stmt->fetchAll();
    }
} catch (Throwable $e) {
    $featuredEvents = [];
}

$pageTitle = 'Accueil — EventHub';
require __DIR__ . '/../includes/header.php';
?>

<main>
    <!-- Section Hero -->
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                </svg>
                <span>Plateforme de gestion d'évènements</span>
            </div>

            <?php if ($user): ?>
                <h1 class="hero-title">
                    Bienvenue, <span><?= e($user['name']) ?></span>
                </h1>
                <p class="hero-subtitle">
                    Consultez le catalogue des évènements à venir, gérez vos inscriptions en temps réel et suivez toutes vos participations depuis votre espace.
                </p>
                <div class="hero-actions">
                    <a href="events.php" class="btn-hero-primary">Explorer les évènements</a>
                    <?php if (in_array($user['role'], ['organizer', 'admin'], true)): ?>
                        <a href="event_create.php" class="btn-hero-secondary">+ Créer un évènement</a>
                    <?php else: ?>
                        <a href="profile.php" class="btn-hero-secondary">Accéder à mon profil</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <h1 class="hero-title">
                    Découvrez, réservez et organisez vos <span>évènements</span>
                </h1>
                <p class="hero-subtitle">
                    Une plateforme simple et rapide pour explorer les conférences, ateliers et rencontres près de chez vous, et suivre vos participations en un clic.
                </p>
                <div class="hero-actions">
                    <a href="events.php" class="btn-hero-primary">Voir le catalogue</a>
                    <a href="register.php" class="btn-hero-secondary">Créer un compte</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section Fonctionnalités -->
    <section class="home-section">
        <div class="section-header">
            <div>
                <h2>Comment ça fonctionne ?</h2>
                <p>Tout ce dont vous avez besoin pour participer ou organiser sereinement.</p>
            </div>
        </div>

        <div class="grid-3">
            <article class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </div>
                <h3>1. Explorez le catalogue</h3>
                <p>Parcourez l'ensemble des évènements disponibles avec les dates, lieux, places restantes et détails complets.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </div>
                <h3>2. Inscrivez-vous en un clic</h3>
                <p>Réservez votre place instantanément depuis votre compte participant et retrouvez votre historique à tout moment.</p>
            </article>

            <article class="feature-card">
                <div class="feature-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <h3>3. Pilotez vos évènements</h3>
                <p>Les organisateurs et administrateurs disposent d'outils dédiés pour publier des évènements et gérer les utilisateurs.</p>
            </article>
        </div>
    </section>

    <!-- Section Aperçu des évènements (si la BDD contient des évènements) -->
    <?php if (!empty($featuredEvents)): ?>
        <section class="home-section">
            <div class="section-header">
                <div>
                    <h2>Évènements à la une</h2>
                    <p>Les prochains rendez-vous ouverts aux inscriptions.</p>
                </div>
                <a href="events.php" class="section-link">Voir tout le catalogue &rarr;</a>
            </div>

            <div class="grid-3">
                <?php foreach ($featuredEvents as $ev): ?>
                    <article class="event-preview-card">
                        <div>
                            <?php if (!empty($ev['starts_at'])): ?>
                                <span class="event-date-tag">
                                    <?= e(date('d/m/Y à H:i', strtotime((string)$ev['starts_at']))) ?>
                                </span>
                            <?php endif; ?>
                            <h3><?= e((string)$ev['title']) ?></h3>
                            <p>
                                <?php
                                $desc = (string)($ev['description'] ?? '');
                                echo e(mb_strlen($desc) > 110 ? mb_substr($desc, 0, 110) . '...' : $desc);
                                ?>
                            </p>
                        </div>
                        <a href="event.php?id=<?= (int)$ev['id'] ?>" class="btn-nav-outline" style="text-align: center;">
                            Voir les détails
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>