import { test as base, expect } from '@playwright/test';
import { attendreBandeauCookies } from './outils';

/** Bandeau et panneau de cookies (tarteaucitron). Ici, aucun choix n'est fait d'avance. */
const test = base;

test('« Tout accepter » ferme le bandeau et le choix est retenu', async ({ page }) => {
  await page.goto('/');
  await attendreBandeauCookies(page);
  const bandeau = page.locator('#tarteaucitronAlertBig');
  await expect(bandeau).toBeVisible();

  await page.getByRole('button', { name: /Tout accepter/ }).first().click();

  await expect(bandeau).toBeHidden();
  await page.goto('/a-propos');
  await expect(page.locator('#tarteaucitronAlertBig')).toBeHidden();
});

test('« Tout refuser » ferme le bandeau', async ({ page }) => {
  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.locator('#tarteaucitronAllDenied2').click();
  await expect(page.locator('#tarteaucitronAlertBig')).toBeHidden();
});

test('« Personnaliser » ouvre le panneau, qui défile jusqu’au bouton Enregistrer', async ({ page }) => {
  await page.setViewportSize({ width: 1024, height: 600 });
  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.locator('#tarteaucitronCloseAlert').click();

  const panneau = page.locator('#tarteaucitron');
  await expect(panneau).toBeVisible();
  const enregistrer = page.locator('#tarteaucitronSaveButton');
  // Défilement à la molette, comme un visiteur (un défilement par script
  // marcherait même si le panneau était bloqué en overflow: hidden).
  await page.locator('#tarteaucitronServices').hover();
  await page.mouse.wheel(0, 3000);
  await expect(enregistrer).toBeInViewport();

  await page.locator('#tarteaucitronClosePanel').click();
  await expect(panneau).toBeHidden();
});

test('le lien « Politique de confidentialité » du bandeau mène à la page', async ({ page }) => {
  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.locator('#tarteaucitronAlertBig #tarteaucitronPrivacyUrl').click();
  await expect(page).toHaveURL(/\/politique-de-confidentialite$/);
});

test('« Gérer mes cookies » sur la page Cookies rouvre le panneau', async ({ page, context, baseURL }) => {
  await context.addCookies([{ name: 'bondiz_tarteaucitron', value: '!gtag=false', url: baseURL! }]);
  await page.goto('/cookies');
  await attendreBandeauCookies(page);
  await page.getByRole('link', { name: 'Gérer mes cookies' }).click();
  await expect(page.locator('#tarteaucitron')).toBeVisible();
});

test('rien n’est envoyé à Google Analytics ni à Matomo avant l’accord du visiteur', async ({ page }) => {
  const versGoogle: string[] = [];
  page.on('request', (requete) => {
    if (/googletagmanager\.com|google-analytics\.com|82\.165\.188\.233/.test(requete.url())) versGoogle.push(requete.url());
  });

  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.goto('/a-propos');
  await page.waitForTimeout(1000);
  expect(versGoogle, 'mesure d’audience avant tout choix').toEqual([]);

  await page.locator('#tarteaucitronAllDenied2').click();
  await page.goto('/');
  await page.waitForTimeout(1000);
  expect(versGoogle, 'mesure d’audience après refus').toEqual([]);
});

// Google Analytics est désactivé (balises en commentaire) : il ne doit pas se
// charger, même après « Tout accepter ». S'il est réactivé, remettre l'attente
// de la requête googletagmanager.com/gtag/js à la place de ce test.
test('Google Analytics reste désactivé, même après « Tout accepter »', async ({ page }) => {
  const versGoogle: string[] = [];
  page.on('request', (requete) => {
    if (/googletagmanager\.com|google-analytics\.com/.test(requete.url())) versGoogle.push(requete.url());
  });
  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.locator('#tarteaucitronPersonalize2').click();
  await page.goto('/a-propos');
  await page.waitForTimeout(1000);
  expect(versGoogle).toEqual([]);
});

test('Matomo se charge une fois les cookies acceptés', async ({ page }) => {
  await page.goto('/');
  await attendreBandeauCookies(page);
  const chargement = page.waitForRequest(/82\.165\.188\.233:8081\/matomo\.js/);
  await page.locator('#tarteaucitronPersonalize2').click();
  await chargement;
});

test('Matomo figure dans le panneau de personnalisation', async ({ page }) => {
  await page.goto('/');
  await attendreBandeauCookies(page);
  await page.locator('#tarteaucitronCloseAlert').click();
  await expect(page.locator('#tarteaucitron')).toContainText('Matomo');
});
