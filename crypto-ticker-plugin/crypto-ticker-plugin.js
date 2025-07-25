import { store, getContext } from '@wordpress/interactivity';

store( 'cryptonewsTicker', {
    actions: {
        init: () => {
            const { context } = getContext();
            const fetchPrice = async () => {
                try {
                    const url  = context.endpoint + "price/" + context.id;
                    const res  = await fetch( url );
                    if ( ! res.ok ) throw new Error( `HTTP ${ res.status }` );
                    const json = await res.json();
                    if ( json?.current_price !== undefined ) {
                        context.price = json.current_price;
                    }
                } catch ( err ) {
                    console.error( 'CryptoTicker:', err );
                }
            };

            fetchPrice();
            setInterval( fetchPrice, 60000 );
        },
    },
} );
