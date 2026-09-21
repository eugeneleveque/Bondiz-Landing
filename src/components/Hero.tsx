import React from 'react';

const Hero: React.FC = () => {
  return (
    <section className="section" style={{
      minHeight: '100vh',
      display: 'flex',
      alignItems: 'center',
      paddingTop: 'var(--hero-padding-top, 60px)',
      paddingBottom: '60px'
    }}>
      <div className="container" style={{
        display: 'flex',
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: '50px',
        alignItems: 'center',
        justifyContent: 'center'
      }}>
        <div className="animate-fade-in hero-content">
          <h1 style={{ marginBottom: '20px' }}>
            Rencontrez des <span className="text-gradient">amis</span>, pas juste des profils.
          </h1>
          <p style={{ fontSize: '1.2rem', opacity: 0.9, marginBottom: '40px', maxWidth: '500px' }}>
            Bondiz est l'application qui révolutionne les rencontres amicales. Trouvez des personnes qui partagent vos passions et participez à des activités réelles.
          </p>
          <div className="hero-buttons" style={{ display: 'flex', gap: '20px', flexWrap: 'wrap' }}>
            <a href="#download" className="btn-cta">Démarrer l'aventure</a>
            <a href="#about" style={{
              border: '2px solid rgba(255,255,255,0.3)',
              padding: '15px 30px',
              borderRadius: '50px',
              fontWeight: 600
            }}>En savoir plus</a>
          </div>
        </div>

        <div className="animate-fade-in hero-image-container">
          <div style={{
            position: 'absolute',
            top: '50%',
            left: '50%',
            transform: 'translate(-50%, -50%)',
            width: 'min(400px, 90vw)',
            height: 'min(400px, 90vw)',
            background: 'var(--color-pink)',
            filter: 'blur(100px)',
            opacity: 0.3,
            zIndex: -1
          }}></div>

          <div className="glass" style={{
            width: 'min(280px, 80vw)',
            height: 'min(560px, 150vw)',
            borderRadius: '40px',
            margin: '0 auto',
            border: '8px solid #222',
            overflow: 'hidden',
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            alignItems: 'center',
            padding: '20px'
          }}>
            <img src="/assets/images/logo_white.png" alt="Bondiz Logo" style={{ width: 'min(150px, 40vw)', marginBottom: '20px' }} />
          </div>
        </div>
      </div>
    </section>
  );
};

export default Hero;
