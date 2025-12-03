FROM node:20-alpine

WORKDIR /app

# O código do Vue virá via volume
# Quando criarmos o projeto, o npm install vai rodar dentro do container

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
