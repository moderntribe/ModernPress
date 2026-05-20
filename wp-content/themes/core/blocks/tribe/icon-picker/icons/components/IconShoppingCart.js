import * as React from 'react';
const SvgIconShoppingCart = ( props ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="1em"
		height="1em"
		fill="none"
		viewBox="0 0 64 64"
		{ ...props }
	>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M13 60a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM51 60a5 5 0 1 0 0-10 5 5 0 0 0 0 10ZM56 42H12.242c-2.516 0-3.914-2.91-2.342-4.874L14 32 10.368 6.576A3 3 0 0 0 7.398 4H4"
		/>
		<path
			stroke="currentColor"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M11.142 12H56l-4.718 15.724A6 6 0 0 1 45.536 32H14"
		/>
	</svg>
);
export default SvgIconShoppingCart;
