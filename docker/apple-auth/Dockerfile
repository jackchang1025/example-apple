# 使用官方的Node.js Alpine镜像作为基础镜像
FROM node:alpine

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . .

# 安装Node.js项目的npm包
RUN npm config set registry https://registry.npmmirror.com

RUN npm install express

# 设置 ENTRYPOINT
ENTRYPOINT ["node", "app.js"]
#CMD ["node", "app.js"]