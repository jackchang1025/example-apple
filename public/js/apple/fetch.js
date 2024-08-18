// 创建一个通用的 AJAX 请求函数

const csrf_token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');


window.fetchRequest = function (url, method,data,header) {

    const defaultHeader = {
        'Content-Type': 'application/json',
    };

    // 合并请求头
    const headers = Object.assign(defaultHeader, header);

    return fetch(url, {
        method: method,
        headers: headers,
        body: JSON.stringify(data),
    })
        .then(response => {

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log(data);

            if (data.code === 302) {
                return window.location.href = data.url ?? '/index/signin';
            }
            return data;
        }).catch(err => {
            console.error(err);

            throw new Error(err);
        });
};
