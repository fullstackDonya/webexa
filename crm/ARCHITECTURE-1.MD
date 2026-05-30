┌─────────────────────────────────────────────────────┐
│  VOTRE CRM (webitech.fr)                           │
│  ┌──────────────────────────────────────┐          │
│  │ Google Cloud Project (UN SEUL)       │          │
│  │ CLIENT_ID: xxxxx.apps.googleusercontent.com │  │
│  │ CLIENT_SECRET: yyyy                  │          │
│  └──────────────────────────────────────┘          │
└─────────────────────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
   ┌────▼────┐    ┌────▼────┐    ┌────▼────┐
   │Entreprise│    │Entreprise│    │Entreprise│
   │    A     │    │    B     │    │    C     │
   └──────────┘    └──────────┘    └──────────┘
   customer_id=1   customer_id=2   customer_id=3
   
   Chaque entreprise:
   ✅ Autorise VOTRE app (même CLIENT_ID)
   ✅ Reçoit SES PROPRES tokens OAuth
   ✅ Tokens stockés avec SON customer_id