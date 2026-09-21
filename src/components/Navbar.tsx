import React from 'react';

// Même header que le blog (blog/templates/base.html.twig)
const Navbar: React.FC = () => {
  return (
    <nav className="navbar">
      <div className="container nav-content">
        <a href="/" className="logo">
          <img src="/assets/images/logo_header.png" alt="Bondiz Logo" className="header-logo" />
        </a>
        <div className="nav-links">
          <a href="/" className="nav-link">Accueil</a>
          <a href="/posts" className="nav-link">Articles</a>
          <a href="#" className="nav-link">Partenaires</a>
          <a href="/a-propos" className="nav-link active">À propos</a>
        </div>
        <a href="/login" className="btn btn-primary">Espace Membre</a>
      </div>
    </nav>
  );
};

export default Navbar;
