#!pip install pandas requests beautifulsoup4 tqdm

import requests
import pandas as pd
import os
import time
import re
from bs4 import BeautifulSoup
from urllib.parse import urljoin, quote
from datetime import datetime
from tqdm import tqdm

# ======================================================
# CONFIGURATION
# ======================================================

# Try to mount Google Drive if in Colab
try:
    from google.colab import drive
    drive.mount('/content/drive/')
    csv_dir = '/content/drive/My Drive/Colab Notebooks/leads_webexa'
except:
    csv_dir = './leads_export'

os.makedirs(csv_dir, exist_ok=True)

csv_path = os.path.join(
    csv_dir,
    f"leads_scrapped_{datetime.now().strftime('%Y%m%d_%H%M%S')}.csv"
)

# User Agent pour éviter les blocages
HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language': 'fr-FR,fr;q=0.9,en;q=0.8',
    'Accept-Encoding': 'gzip, deflate, br',
    'DNT': '1',
    'Connection': 'keep-alive',
    'Upgrade-Insecure-Requests': '1'
}


SECTEURS_CIBLES = [
    "agence immobilière",
    "expert-comptable",
    "transport routier",
    "logistique",
    "cabinet conseil",
    "hôtel restaurant",
    "cabinet médical",
    "clinique",
    "entreprise de nettoyage",
    "agence de voyage",
    "auto-école",
    "architecte",
    "plomberie",
    "électricité",
    "peinture",
    "menuiserie",
    "assurance",
    "banque",
    "entreprise de construction"
]

VILLES_CIBLES = [
    "Paris",
    "Marseille",
    "Lyon",
    "Toulouse",
    "Nice",
    "Nantes",
    "Strasbourg",
    "Montpellier",
    "Bordeaux",
    "Lille"
]

DELAI_REQUETES = 2  # secondes entre les requêtes



# ======================================================
# SCORE COMMERCIAL WEBEXA
# ======================================================

def calcul_score(lead):
    """Calcule un score commercial de 0-100"""
    
    score = 0
    
    # Secteur ciblé
    secteur = (lead.get("secteur", "") or "").lower()
    for mot_cle in ["immobilier", "comptab", "transport", "logistique", "conseil", 
                     "restaurant", "médical", "clinique", "nettoyage", "voyage", 
                     "auto-école", "architecte", "plomber", "électr", "peinture",
                     "menuiser", "assurance", "banque", "construction"]:
        if mot_cle in secteur:
            score += 20
            break
    
    # Téléphone présent
    if lead.get("telephone"):
        score += 15
    
    # Email présent
    if lead.get("email"):
        score += 15
    
    # Site web présent
    if lead.get("site"):
        score += 10
    
    # Adresse complète
    if lead.get("adresse"):
        score += 10
    
    # Code postal
    if lead.get("code_postal"):
        score += 5
    
    return min(score, 100)



# ======================================================
# SCRAPER PAGES JAUNES
# ======================================================

def scraper_pages_jaunes(secteur, ville):
    """Scrape Pages Jaunes pour un secteur et une ville"""
    
    resultats = []
    url_base = "https://www.pagesjaunes.fr/search"
    
    try:
        params = {
            "quoi": secteur,
            "ou": ville,
            "page": 1
        }
        
        # Paginer pour récupérer plus de résultats
        for page in range(1, 4):  # 3 pages max
            params["page"] = page
            
            response = requests.get(
                url_base,
                params=params,
                headers=HEADERS,
                timeout=15
            )
            
            if response.status_code != 200:
                continue
            
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Trouver les éléments des entreprises
            items = soup.find_all('div', {'class': re.compile('.*result-item.*')})
            
            for item in items:
                try:
                    # Nom
                    nom_elem = item.find('h2', {'class': re.compile('.*name.*')})
                    nom = nom_elem.get_text(strip=True) if nom_elem else ""
                    
                    # Adresse
                    adresse_elem = item.find('div', {'class': re.compile('.*address.*')})
                    adresse = adresse_elem.get_text(strip=True) if adresse_elem else ""
                    
                    # Téléphone
                    tel_elem = item.find('div', {'class': re.compile('.*phone.*')})
                    telephone = tel_elem.get_text(strip=True) if tel_elem else ""
                    telephone = re.sub(r'\D', '', telephone)[-10:] if telephone else ""
                    
                    # Email (souvent pas directement affiché)
                    email = ""
                    
                    # Site web
                    site_elem = item.find('a', {'class': re.compile('.*website.*')})
                    site = site_elem.get('href', '') if site_elem else ""
                    
                    # Code postal
                    code_postal = re.search(r'\b(\d{5})\b', adresse)
                    code_postal = code_postal.group(1) if code_postal else ""
                    
                    if nom:
                        lead = {
                            "source": "Pages Jaunes",
                            "nom": nom,
                            "secteur": secteur,
                            "adresse": adresse,
                            "code_postal": code_postal,
                            "ville": ville,
                            "telephone": telephone,
                            "email": email,
                            "site": site,
                            "date_scrape": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                        }
                        lead["score"] = calcul_score(lead)
                        resultats.append(lead)
                
                except Exception as e:
                    print(f"Erreur parsing item: {e}")
                    continue
            
            time.sleep(DELAI_REQUETES)
        
    except Exception as e:
        print(f"Erreur scraping Pages Jaunes {secteur}/{ville}: {e}")
    
    return resultats



# ======================================================
# SCRAPER BING LOCAL
# ======================================================

def scraper_bing_local(secteur, ville):
    """Scrape Bing Local pour les entreprises"""
    
    resultats = []
    
    try:
        url = "https://www.bing.com/search"
        
        # Chercher "entreprises" + secteur + ville
        query = f"{secteur} {ville} France contact téléphone"
        
        params = {
            "q": query,
            "first": 1
        }
        
        response = requests.get(
            url,
            params=params,
            headers=HEADERS,
            timeout=15
        )
        
        if response.status_code == 200:
            soup = BeautifulSoup(response.content, 'html.parser')
            
            # Chercher les résultats locaux
            items = soup.find_all('div', {'class': 'b_algo'})
            
            for item in items[:5]:  # Top 5
                try:
                    title_elem = item.find('h2')
                    if not title_elem:
                        continue
                    
                    titre = title_elem.get_text(strip=True)
                    
                    # Extraire le lien
                    link_elem = item.find('a')
                    url_entreprise = link_elem.get('href', '') if link_elem else ""
                    
                    lead = {
                        "source": "Bing Local",
                        "nom": titre,
                        "secteur": secteur,
                        "ville": ville,
                        "telephone": "",
                        "email": "",
                        "site": url_entreprise,
                        "adresse": "",
                        "code_postal": "",
                        "date_scrape": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                    }
                    lead["score"] = calcul_score(lead)
                    resultats.append(lead)
                
                except:
                    continue
        
        time.sleep(DELAI_REQUETES)
        
    except Exception as e:
        print(f"Erreur scraping Bing: {e}")
    
    return resultats



# ======================================================
# NETTOYAGE DES DONNÉES
# ======================================================

def nettoyer_lead(lead):
    """Nettoie et valide les données du lead"""
    
    # Supprimer espaces inutiles
    for key in ['nom', 'adresse', 'email', 'telephone', 'site']:
        if key in lead:
            lead[key] = lead[key].strip() if lead[key] else ""
    
    # Valider email
    if lead.get('email'):
        if not re.match(r'^[\w\.-]+@[\w\.-]+\.\w+$', lead['email']):
            lead['email'] = ""
    
    # Valider téléphone (10 chiffres)
    if lead.get('telephone'):
        digits = re.sub(r'\D', '', lead['telephone'])
        if len(digits) >= 10:
            lead['telephone'] = digits[-10:]
        else:
            lead['telephone'] = ""
    
    # Ajouter http si manquant
    if lead.get('site') and not lead['site'].startswith('http'):
        lead['site'] = 'https://' + lead['site']
    
    return lead



# ======================================================
# EXÉCUTION PRINCIPALE
# ======================================================

def lancer_scraping():
    """Lance le scraping complet"""
    
    tous_les_leads = []
    
    print("🚀 Démarrage du scraping de leads...")
    print(f"📍 Secteurs: {len(SECTEURS_CIBLES)}")
    print(f"📍 Villes: {len(VILLES_CIBLES)}")
    print()
    
    # Combiner secteurs et villes
    total_iterations = len(SECTEURS_CIBLES) * len(VILLES_CIBLES)
    
    with tqdm(total=total_iterations, desc="Scraping") as pbar:
        
        for secteur in SECTEURS_CIBLES:
            for ville in VILLES_CIBLES:
                
                # Pages Jaunes
                leads_pj = scraper_pages_jaunes(secteur, ville)
                tous_les_leads.extend(leads_pj)
                
                # Bing Local (tous les 2 secteurs pour ne pas surcharger)
                if SECTEURS_CIBLES.index(secteur) % 2 == 0:
                    leads_bing = scraper_bing_local(secteur, ville)
                    tous_les_leads.extend(leads_bing)
                
                pbar.update(1)
    
    print()
    print(f"✅ Total leads extraits: {len(tous_les_leads)}")
    
    # Nettoyer les données
    print("🧹 Nettoyage des données...")
    tous_les_leads = [nettoyer_lead(lead) for lead in tous_les_leads]
    
    # Supprimer les doublons (par nom et ville)
    leads_uniques = []
    seen = set()
    
    for lead in tous_les_leads:
        key = (lead.get('nom', '').lower(), lead.get('ville', '').lower())
        if key not in seen:
            seen.add(key)
            leads_uniques.append(lead)
    
    print(f"✅ Après dédoublonnage: {len(leads_uniques)} leads uniques")
    
    # Trier par score décroissant
    leads_uniques.sort(key=lambda x: x.get('score', 0), reverse=True)
    
    # Créer DataFrame
    df = pd.DataFrame(leads_uniques)
    
    # Réorganiser les colonnes
    colonnes = ['source', 'nom', 'secteur', 'adresse', 'code_postal', 'ville', 
                'telephone', 'email', 'site', 'score', 'date_scrape']
    
    df = df[[col for col in colonnes if col in df.columns]]
    
    # Sauvegarder
    df.to_csv(csv_path, index=False, encoding='utf-8-sig')
    
    print()
    print(f"💾 Fichier sauvegardé: {csv_path}")
    print(f"📊 Statistiques:")
    print(f"   - Score moyen: {df['score'].mean():.1f}/100")
    print(f"   - Leads avec téléphone: {(df['telephone'] != '').sum()}")
    print(f"   - Leads avec email: {(df['email'] != '').sum()}")
    print(f"   - Leads avec site: {(df['site'] != '').sum()}")
    
    return df


# ======================================================
# POINT D'ENTRÉE
# ======================================================

if __name__ == "__main__":
    df_leads = lancer_scraping()
    print()
    print("Aperçu des top 10 leads:")
    print(df_leads.head(10).to_string())