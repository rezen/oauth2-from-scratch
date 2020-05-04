(function() {

    function fixParams(params) {
        return Object.keys(params).map(function(name) {
            return {
                name,
                value: params[name],
                options: (context.terms[name] || {}).options || []
            };
        }, []);
    }
    const context = {
        terms: []
    };
    context.terms = Object.keys(terms).map(function(key) {
        const found = /options=\[([^\]]+)/.exec(terms[key]);
        const options = (found || ["", ""])[1].split(",").map(p => p.trim()).filter(p => p.length)
        return {
            name: key,
            description: terms[key],
            options: options,
        }
    }).reduce(function(aggr, term) {
        aggr[term.name] = term;
        return aggr;
    }, {});

    function parseQuery(queryString) {
        try {
            queryString = (new URL(queryString)).search;
        } catch (err) {}
        const query = {};
        const pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
        for (let i = 0; i < pairs.length; i++) {
            const pair = pairs[i].split('=');
            query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
        }
        return query;
    }

    Vue.component('error-with-guide', {
        data: function() {
            return {
                attr: false
            }
        },
        template: `<section class="error">
            <strong><slot></slot></strong><br /><br />
            <guide :param="attr"></guide>        
        </section>`,
        created: function() {
            const text = this.$slots.default[0].text;
            this.attr = /\:([a-z_]+)/.exec(text)[1] || false;
        },
    });
    Vue.component('guide', {
        props: ['param'],
        data: function() {
            return {
                hint: false
            }
        },
        template: `<section v-if="hint" style="background:#eee;padding:20px">
                <div v-html="hint"></div>
                </section>`,
        created: function() {
            this.hint = terms[this.param] || false;
        },
    });

    Vue.component('oauth-client-play', {
        data: function() {
            return {
                original: '',
                params: {}
            }
        },
        computed: {
            // a computed getter
            login_url: function() {
                return "http://localhost:4444/server/oauth/authorize?" + this.query;
            },
            query: function() {
                return this.params.map(function(p) {
                    return p.name + "=" + p.value;
                }).join("&");
            }
        },

        template: `<form>
            <button type="submit">Next</button>
        <todo-item v-bind:set-params="params"></todo-item>
            </form>`,
        created: function() {
            this.original = this.$slots.default[0].text;
            const params = parseQuery(this.original);
            this.params = fixParams(params);
        },
    });

    Vue.component('todo-item', {
        props: ['set-params', 'fmt'],
        data: function() {
            const text = ((this.$slots.default || [])[0] || {}).text;
            let params = this.setParams || [];
            if (text && text[0] === "{") {
                params = fixParams(JSON.parse(text));
            } else if (text) {
                params = fixParams(parseQuery(text));
            }

            return {
                format: this.fmt || 'query',
                params: params,
            }
        },
        computed: {
            query_formatted: function() {
                if (this.format === "query") {
                    return this.params.map(function(p) {
                        return p.name + "=" + p.value;
                    }).join("\n&");
                }

                return JSON.stringify(this.params.reduce(function(aggr, p) {
                    aggr[p.name] = p.value;
                    return aggr;
                }, {}), null, 2);
            }
        },
        template: `<section>
        <h5>Raw</h5>
        <pre style="background:#eee;padding:20px;">{{ query_formatted }}</pre>
        <section style="background:#eee;padding:20px;">
        <div v-for="p in params" :key="p.name" :data-key="p.name">
<strong>{{ p.name }}</strong>
    <input v-if="p.options.length==0" type="text" v-model="p.value" :name="p.name" />
<select v-if="p.options.length!==0" v-model="p.value" :name="p.name">
          <option v-for="o in p.options">{{ o }}</option>
</select>
<guide :param="p.name"></guide>
</div></section>
        </section>`,
        watch: {},
    });
    document.addEventListener("DOMContentLoaded", function() {
        const app = new Vue({
            el: '#app',
            data: {}
        });
    });
})();