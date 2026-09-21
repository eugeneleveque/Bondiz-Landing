import Navbar from './components/Navbar';
import Footer from './components/Footer';
import './index.css';

const cardStyle = {
  padding: '50px 40px',
  borderRadius: '40px',
  textAlign: 'left' as const,
  lineHeight: 1.9,
  fontSize: '1.15rem',
  opacity: 0.95
};

function App() {
  return (
    <div className="app">
      <Navbar />
      <main>
        <section className="section" style={{ paddingBottom: '20px' }}>
          <div className="container animate-fade-in" style={{ maxWidth: '800px' }}>
            <h1 style={{ marginBottom: '24px' }}>
              Pourquoi avons-nous créé <span className="text-gradient">Bondiz</span> ?
            </h1>
            <p style={{ fontSize: '1.25rem', lineHeight: 1.8, opacity: 0.9 }}>
              Bondiz est né d’un constat simple : aujourd’hui, il n’a jamais été aussi facile de parler à quelqu’un en ligne… mais parfois difficile de créer de vraies connexions.
            </p>
          </div>
        </section>

        <section className="section" style={{ padding: '30px 0' }}>
          <div className="container" style={{ maxWidth: '800px' }}>
            <div className="glass" style={cardStyle}>
              <p>
                Nous avons voulu imaginer une alternative plus humaine aux plateformes traditionnelles : une application qui aide les gens à se rencontrer dans la vraie vie, autour d’activités et d’expériences partagées.
              </p>
              <br />
              <p>Notre ambition est simple : remettre l’expérience réelle au cœur des rencontres.</p>
              <br />
              <p>
                Pour cela, Bondiz s’appuie sur des partenaires locaux, des activités accessibles et une approche plus naturelle des échanges.
              </p>
              <br />
              <p>Ici, les relations commencent par un moment vécu ensemble.</p>
              <br />
              <p><strong>Bondiz, c’est une nouvelle façon de sortir, découvrir, partager… et rencontrer.</strong></p>
            </div>
          </div>
        </section>

        <section className="section" style={{ padding: '30px 0' }}>
          <div className="container" style={{ maxWidth: '800px' }}>
            <div className="glass" style={cardStyle}>
              <h2 style={{ fontSize: '2rem', marginBottom: '20px' }}>
                Une <span className="text-gradient">startup</span> en construction
              </h2>
              <p>
                Bondiz est un projet actuellement en phase de développement. Nous travaillons activement à la création de l’application, au développement de notre réseau de partenaires locaux et à la construction d’une expérience pensée autour du lien social réel.
              </p>
              <br />
              <p>
                Notre objectif est de lancer progressivement la plateforme à l’échelle locale avant d’étendre le projet à d’autres villes.
              </p>
            </div>
          </div>
        </section>

        <section className="section" style={{ padding: '30px 0 100px' }}>
          <div className="container" style={{ maxWidth: '800px' }}>
            <div className="glass" style={cardStyle}>
              <h2 style={{ fontSize: '2rem', marginBottom: '20px' }}>
                Une équipe de <span className="text-gradient">8 personnes</span>
              </h2>
              <p>
                Bondiz est porté par une équipe de 8 étudiants aux profils complémentaires : marketing, design, développement, data, cybersécurité
              </p>
              <br />
              <p>
                Cette diversité de compétences nous permet de construire un projet à la fois créatif, technique et centré sur les usages réels des utilisateurs.
              </p>
              <br />
              <p>
                Au-delà de l’application, nous partageons surtout une même ambition : proposer une manière plus authentique de créer du lien.
              </p>
            </div>
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}

export default App;
