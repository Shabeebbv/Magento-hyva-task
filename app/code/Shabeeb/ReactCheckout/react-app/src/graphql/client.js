// Sends one GraphQL operation to Magento and returns `data`.
// Throws an Error with Magento's message if the response contains `errors`.
export async function graphql(config, query, variables = {}) {
    const response = await fetch(config.graphqlUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Store: config.storeCode },
        body: JSON.stringify({ query, variables })
    });
    const result = await response.json();
    if (result.errors && result.errors.length) {
        throw new Error(result.errors[0].message);
    }
    return result.data;
}
