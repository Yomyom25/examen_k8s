# 📦 Examen Kubernetes - Aplicación Multipod

Sistema de Inventario desplegado en Kubernetes con arquitectura multipod, base de datos MySQL y phpMyAdmin.

---

## 📋 Tabla de Contenidos

- [Descripción](#descripción)
- [Tecnologías Utilizadas](#tecnologías-utilizadas)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Requisitos Previos](#requisitos-previos)
- [Instalación Local](#instalación-local)
- [Contenerización](#contenerización)
- [Despliegue en Kubernetes](#despliegue-en-kubernetes)
- [Acceso a la Aplicación](#acceso-a-la-aplicación)
- [Arquitectura](#arquitectura)
- [Variables de Entorno](#variables-de-entorno)

---

## 📝 Descripción

Aplicación web de gestión de inventario que cumple con los siguientes requisitos del examen:

- ✅ **Multipod**: Mínimo 2 réplicas funcionando
- ✅ **Downward API**: Inyección de POD_NAME y NODE_NAME
- ✅ **ConfigMap**: Variable BANNER configurable
- ✅ **Service NodePort**: Exposición externa en puerto 30005
- ✅ **Rolling Update**: Estrategia de actualización sin downtime
- ✅ **Base de Datos**: MySQL 5.7 con phpMyAdmin

---

## 🛠️ Tecnologías Utilizadas

- **Frontend/Backend**: PHP 8.2
- **Base de Datos**: MySQL 5.7
- **Administración DB**: phpMyAdmin
- **Contenedores**: Docker
- **Orquestación**: Kubernetes
- **Registry**: Docker Hub

---

## 📂 Estructura del Proyecto

```
examen-k8s/
│
├── app/
│   └── src/
│       ├── index.php          # Aplicación principal
│       ├── db.php             # Conexión a base de datos
│       ├── style.css          # Estilos CSS
│       └── setup.sql          # Script de base de datos
│
├── k8s/
│   ├── webapp_deployment.yaml # Deployment de la aplicación (2 réplicas)
│   ├── webapp_service.yaml    # Service NodePort (puerto 30005)
│   ├── configmap.yaml         # ConfigMap con BANNER y DB config
│   ├── bd_deployment.yaml     # Deployment MySQL + phpMyAdmin
│   └── bd_service.yaml        # Service para MySQL
│
├── Dockerfile                 # Imagen de la aplicación
└── README.md                  # Este archivo
```

---

## ⚙️ Requisitos Previos

- Docker Desktop instalado
- Kubernetes habilitado (Docker Desktop o Minikube)
- kubectl configurado
- Cuenta en Docker Hub (para subir imagen)

---

## 💻 Instalación Local

### 1. Clonar el repositorio

```bash
git clone <tu-repositorio>
cd examen-k8s
```

### 2. Probar localmente con XAMPP/WAMP (Opcional)

```bash
# Copiar archivos a htdocs
cp -r app/src/* C:/xampp/htdocs/inventario/

# Importar setup.sql en phpMyAdmin
# Acceder a: http://localhost/inventario
```

---

## 🐳 Contenerización

### 1. Construir la imagen

```bash
docker build -t yom25/app-k8s:v1 .
```

### 2. Probar localmente

```bash
docker run -d -p 8080:80 \
  -e BANNER="Bienvenido al ITM" \
  -e DB_HOST="host.docker.internal" \
  -e DB_NAME="examen_db" \
  -e DB_USER="root" \
  -e DB_PASS="root" \
  yom25/app-k8s:v1
```

Acceder a: http://localhost:8080

### 3. Subir a Docker Hub

```bash
# Login
docker login

# Push
docker push yom25/app-k8s:v1
```

---

## ☸️ Despliegue en Kubernetes

### 1. Aplicar ConfigMap (primero)

```bash
kubectl apply -f k8s/configmap.yaml
```

### 2. Desplegar Base de Datos

```bash
kubectl apply -f k8s/bd_deployment.yaml
kubectl apply -f k8s/bd_service.yaml
```

### 3. Esperar que MySQL esté listo

```bash
kubectl get pods -w
# Esperar hasta que bd-deployment esté en estado Running
```

### 4. Importar la base de datos

**Opción A: Port-forward a phpMyAdmin**

```bash
# En una terminal separada
kubectl port-forward deployment/bd-deployment 8080:80

# Acceder a http://localhost:8080
# Usuario: root | Contraseña: root | Servidor: 127.0.0.1
# Ir a "Importar" y subir app/src/setup.sql
```

**Opción B: Desde línea de comandos**

```bash
# Copiar SQL al pod
kubectl cp app/src/setup.sql bd-deployment-<pod-id>:/tmp/setup.sql

# Ejecutar dentro del contenedor
kubectl exec -it bd-deployment-<pod-id> -c mysql -- \
  mysql -u root -proot examen_db < /tmp/setup.sql
```

### 5. Desplegar la Aplicación

```bash
kubectl apply -f k8s/webapp_deployment.yaml
kubectl apply -f k8s/webapp_service.yaml
```

### 6. Verificar despliegue

```bash
# Ver todos los pods
kubectl get pods

# Verificar réplicas de la app
kubectl get pods -l app=webapp

# Ver logs de un pod específico
kubectl logs <nombre-del-pod>
```

---

## 🌐 Acceso a la Aplicación

### Aplicación Principal

```
http://localhost:30005
```

### Verificación de Multipod

Cada vez que recargues la página, verás en el header:

- **POD**: webapp-deploy-xxxxxxxx-xxxxx (cambia entre pods)
- **NODO**: docker-desktop (o nombre de tu nodo)

El fondo del header será **verde** cuando esté corriendo en Kubernetes.

### phpMyAdmin (con port-forward)

```bash
kubectl port-forward deployment/bd-deployment 8080:80
```

Acceder a: http://localhost:8080

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────┐
│           Service: webapp-service               │
│              NodePort: 30005                    │
└────────────────┬────────────────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
   ┌────▼────┐       ┌────▼────┐
   │  Pod 1  │       │  Pod 2  │
   │ webapp  │       │ webapp  │
   └────┬────┘       └────┬────┘
        │                 │
        └────────┬────────┘
                 │
        ┌────────▼────────┐
        │ mysql-service   │
        │  ClusterIP      │
        └────────┬────────┘
                 │
        ┌────────▼────────────┐
        │   bd-deployment     │
        │  - MySQL 5.7        │
        │  - phpMyAdmin       │
        └─────────────────────┘
```

---

## 🔐 Variables de Entorno

### ConfigMap (webapp-config)

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `BANNER` | "Bienvenido al ITM" | Mensaje del header |
| `DB_HOST` | "mysql-service" | Nombre del servicio MySQL |
| `DB_NAME` | "examen_db" | Nombre de la base de datos |
| `DB_USER` | "root" | Usuario de MySQL |

### Downward API

| Variable | Origen | Descripción |
|----------|--------|-------------|
| `POD_NAME` | `metadata.name` | Nombre del pod actual |
| `NODE_NAME` | `spec.nodeName` | Nodo donde corre el pod |

### Directas

| Variable | Valor | Descripción |
|----------|-------|-------------|
| `DB_PASS` | "root" | Contraseña de MySQL |

---

## 🧪 Pruebas

### Verificar escalabilidad

```bash
# Ver pods en ejecución
kubectl get pods -l app=webapp

# Escalar a 3 réplicas
kubectl scale deployment webapp-deploy --replicas=3

# Verificar
kubectl get pods -l app=webapp
```

### Verificar Rolling Update

```bash
# Cambiar imagen (simular actualización)
kubectl set image deployment/webapp-deploy webapp=yom25/app-k8s:v2

# Ver el proceso
kubectl rollout status deployment/webapp-deploy

# Ver historial
kubectl rollout history deployment/webapp-deploy
```

### Verificar ConfigMap

```bash
# Ver ConfigMap actual
kubectl get configmap webapp-config -o yaml

# Editar ConfigMap
kubectl edit configmap webapp-config

# Reiniciar pods para aplicar cambios
kubectl rollout restart deployment/webapp-deploy
```

---

## 🐛 Solución de Problemas

### Pod no inicia

```bash
# Ver estado detallado
kubectl describe pod <nombre-pod>

# Ver logs
kubectl logs <nombre-pod>
```

### Error de conexión a BD

```bash
# Verificar que MySQL esté corriendo
kubectl get pods -l app=mysql

# Ver logs de MySQL
kubectl logs <bd-pod> -c mysql

# Verificar que setup.sql se haya importado
kubectl exec -it <bd-pod> -c mysql -- mysql -u root -proot -e "SHOW DATABASES;"
```

### NodePort no accesible

```bash
# Verificar servicio
kubectl get svc webapp-service

# En Windows, puede ser necesario usar localhost o 127.0.0.1
# En lugar de la IP del nodo
```
