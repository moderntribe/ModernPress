import * as React from 'react';
const SvgIconLikeThumbUp = ( props ) => (
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
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M22 60h30a7.07 7.07 0 0 0 7-6l3-24a5.89 5.89 0 0 0-6-6H38v-8.918a18.13 18.13 0 0 0-5.294-12.04A4.01 4.01 0 0 0 26 6v12L14 32"
		/>
		<path
			stroke="currentColor"
			strokeLinecap="square"
			strokeMiterlimit={ 10 }
			strokeWidth={ 2 }
			d="M14 26H2v34h12z"
		/>
	</svg>
);
export default SvgIconLikeThumbUp;
